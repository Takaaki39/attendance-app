<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRest;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        // 今日から2か月前まで
        $startDate = Carbon::today()->subMonths(2);
        $endDate   = Carbon::today()->subDay();   // 前日まで

        foreach ($users as $user) {

            $date = $startDate->copy();

            while ($date->lte($endDate)) {

                // 土日を除外
                if ($date->isWeekend()) {
                    $date->addDay();
                    continue;
                }

                // ランダム勤務時間 

                // 出勤時間 7:00〜12:00
                $startHour = rand(7, 12);
                $startMin  = rand(0, 59);
                $startTime = $date->copy()->setTime($startHour, $startMin);

                // 退勤は 出勤 + 6〜12 時間の間でランダム
                $workHours = rand(6, 12);
                $endTime   = $startTime->copy()->addHours($workHours)->addMinutes(rand(0, 59));
                $endOfDay = $date->copy()->setTime(23, 59);
                if ($endTime->greaterThan($endOfDay)) {
                    $endTime = $endOfDay;
                }

                // 出勤データ作成
                $attendance = Attendance::create([
                    'user_id'    => $user->id,
                    'date'       => $date->toDateString(),
                    'notes'      => null,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ]);

                // 休憩 0〜5回
                $restCount = rand(0, 5);
                $existingRests = [];

                for ($i = 0; $i < $restCount; $i++) {

                    $attempt = 0;
                    $generated = false;

                    while (!$generated && $attempt < 30) {  // 無限ループ防止
                        $attempt++;

                        // 勤務時間内で休憩開始
                        $restStart = $startTime->copy()->addMinutes(
                            rand(0, $startTime->diffInMinutes($endTime) - 20) // 20分は休憩最低長
                        );

                        // 10〜90分の間で休憩時間を作成
                        $restEnd = $restStart->copy()->addMinutes(rand(10, 90));

                        // 終了が退勤を超えれば調整
                        if ($restEnd->gt($endTime)) {
                            $restEnd = $endTime->copy();
                        }

                        if ($restEnd->lte($restStart)) {
                            continue;
                        }

                        // 重複チェック
                        $overlap = false;
                        foreach ($existingRests as $r) {
                            if ($restStart < $r['end'] && $r['start'] < $restEnd) {
                                $overlap = true;
                                break;
                            }
                        }

                        if ($overlap) {
                            continue; // もう一度ランダム生成
                        }

                        // 重複なし → 保存
                        $existingRests[] = [
                            'start' => $restStart,
                            'end'   => $restEnd,
                        ];

                        AttendanceRest::create([
                            'attendance_id' => $attendance->id,
                            'start_time'    => $restStart,
                            'end_time'      => $restEnd,
                        ]);

                        $generated = true;
                    }
                }

                $date->addDay();
            }
        }
    }
}
