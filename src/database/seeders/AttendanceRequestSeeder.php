<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRest;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRestRequest;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AttendanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ja_JP');

        $users = User::all();

        foreach ($users as $user) {

            // このユーザーの attendance をランダムに 10 件取得
            $attendances = Attendance::where('user_id', $user->id)
                ->inRandomOrder()
                ->limit(10)
                ->get();

            foreach ($attendances as $attendance) {

                if (!$attendance->start_time || !$attendance->end_time) {
                    continue; // 無効データはスキップ
                }

                // ===== Attendance Request 作成 =====
                $reqStart = Carbon::parse($attendance->start_time)
                    ->addMinutes(rand(10, 60));

                $reqEnd = Carbon::parse($reqStart)
                    ->addMinutes(rand(30, 180));

                // 出勤終了時刻以内に調整
                if ($reqEnd->gt(Carbon::parse($attendance->end_time))) {
                    $reqEnd = Carbon::parse($attendance->end_time)->subMinutes(rand(5, 30));
                }

                // リクエスト日：attendance の日付より後〜今日の間
                $requestDate = Carbon::parse($attendance->date)
                    ->addDays(rand(1, 5));

                if ($requestDate->gt(today())) {
                    $requestDate = today()->subDays(rand(1, 3));
                }

                $state = rand(1, 2);
                $request = AttendanceRequest::create([
                    'attendance_id' => $attendance->id,
                    'user_id'       => $attendance->user_id,
                    'start_time'    => $reqStart,
                    'end_time'      => $reqEnd,
                    'state'         => $state,
                    'notes'         => $faker->realText(20),
                    'request_date'  => $requestDate,
                ]);

                // ===== Attendance Rest Request 作成 =====
                $rests = AttendanceRest::where('attendance_id', $attendance->id)
                    ->orderBy('start_time')
                    ->get();

                $usedRanges = [];

                foreach ($rests as $rest) {
                    $restStart = Carbon::parse($rest->start_time);
                    $restEnd   = Carbon::parse($rest->end_time);

                    // リクエスト範囲内に収まる休憩だけを対象にする
                    if ($restStart->lt($reqStart) || $restEnd->gt($reqEnd)) {
                        continue;
                    }

                    // 休憩時間が被らないようにチェック
                    $isOverlap = false;
                    foreach ($usedRanges as $range) {
                        if (
                            $restStart->between($range['start'], $range['end'])
                            || $restEnd->between($range['start'], $range['end'])
                        ) {
                            $isOverlap = true;
                            break;
                        }
                    }
                    if ($isOverlap) continue;

                    // 登録
                    AttendanceRestRequest::create([
                        'request_id' => $request->id,
                        'rest_id'    => $rest->id,
                        'start_time' => $restStart,
                        'end_time'   => $restEnd,
                    ]);

                    $usedRanges[] = [
                        'start' => $restStart,
                        'end'   => $restEnd,
                    ];
                }

                if ($state == 2) {

                    // 勤怠本体の更新
                    $attendance->update([
                        'start_time' => $reqStart,
                        'end_time'   => $reqEnd,
                        'notes'      => $request->notes,
                    ]);

                    // 対応する休憩も request の内容で上書き
                    $restRequests = AttendanceRestRequest::where('request_id', $request->id)->get();

                    foreach ($restRequests as $restReq) {
                        AttendanceRest::where('id', $restReq->rest_id)->update([
                            'start_time' => $restReq->start_time,
                            'end_time'   => $restReq->end_time,
                        ]);
                    }
                }
            }
        }
    }
}
