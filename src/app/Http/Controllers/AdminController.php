<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function list(Request $request)
    {
        // URLやフォームから受け取った日付（なければ今日）
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now();

        $users = User::all();

        return view('admin.attendance.list', compact('date', 'users'));
    }

    public function detail(Request $request, $id = null)
    {
        $reqId = $request->input('request_id');
        if ($id) {
            $attendance = Attendance::where('id', $id)
                ->firstOrFail();
            $requestData = AttendanceRequest::where('attendance_id', $attendance->id)
                ->latest()
                ->first();
            if ($requestData != null && $requestData->state == 2) {
                $requestData = null;
            }
            $user = User::find($attendance->user_id);
        } else if ($reqId) {
            $requestData = AttendanceRequest::find($reqId);
            $user = User::find($requestData->user_id);
            $attendance = null;
        } else {
            $user = User::find($request->user_id);

            $attendance = new Attendance();
            $attendance->user_id = $user->id;
            $attendance->date = Carbon::parse($request->date)->startOfDay();
            $attendance->end_time   = null; // まだ未打刻なので
            $attendance->id = null;
            $requestData = AttendanceRequest::where('user_id', $user->id)
                ->whereDate('start_time', $attendance->date) // 日付比較
                ->where('state', 1) // 申請中
                ->latest()
                ->first();
        }
        return view('admin.attendance.detail', compact('user', 'attendance', 'requestData'));
    }

    public function requestFix(ApplicationRequest $request)
    {
        $attendance = Attendance::find($request->attendance_id);

        $year = intval(str_replace('年', '', $request->year)); // 2025年 → 2025
        $day  = str_replace(['月', '日'], ['-', ''], $request->day); // 3月12日 → 3-12
        $baseDate = Carbon::parse($year . '-' . $day)->startOfDay();

        $parseTime = function ($time) use ($baseDate) {
            if (empty($time)) return null;
            return Carbon::parse($baseDate->format('Y-m-d') . ' ' . $time)->setSeconds(0);
        };

        // attendance が無い場合は新規作成
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $request->user_id,
                'date'    => $baseDate->format('Y-m-d'),
                'start_time' => null,
                'end_time'   => null,
                'notes'      => null,
            ]);
        }

        $attendance->start_time = $parseTime($request->start_time);
        $attendance->end_time = $parseTime($request->end_time);
        $attendance->notes = $request->notes;
        $attendance->save();

        // 受け取った休憩データをまとめる（既存 + 新規）
        $rests = [];

        // 既存休憩
        foreach ($attendance->rests as $i => $rest) {
            $start = $request->rest_start_time[$i] ?? null;
            $end   = $request->rest_end_time[$i] ?? null;

            if (!$start && !$end) continue; // 空ならスキップ

            $rests[] = [
                'id'    => $rest->id,
                'start' => $parseTime($start),
                'end'   => $parseTime($end),
            ];
        }

        // 新規休憩（どちらか入力があったら追加）
        if ($request->new_rest_start_time || $request->new_rest_end_time) {
            $rests[] = [
                'id'    => null,
                'start' => $parseTime($request->new_rest_start_time),
                'end'   => $parseTime($request->new_rest_end_time),
            ];
        }

        // 開始時間でソート（早い順）
        usort($rests, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        // DB に反映（既存はupdate / 新規はinsert）
        foreach ($rests as $rest) {
            if ($rest['id']) {
                // 更新
                AttendanceRest::where('id', $rest['id'])->update([
                    'start_time' => $rest['start'],
                    'end_time'   => $rest['end'],
                ]);
            } else {
                // 新規追加
                AttendanceRest::create([
                    'attendance_id' => $attendance->id,
                    'start_time'    => $rest['start'],
                    'end_time'      => $rest['end'],
                ]);
            }
        }
        return redirect()->route('admin.attendance.list')->with('success', '勤怠修正申請を送信しました。');
    }

    public function staffList()
    {
        $users = User::all();
        return view('admin.staff.list', compact('users'));
    }

    public function staff($id, Request $request)
    {
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now();

        // 指定月の開始日と終了日を取得
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 1日〜月末までの CarbonPeriod オブジェクトを作成
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        $calender = collect($period)->map(fn($day) => $day);

        $user = User::find($id);
        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($a) => $a->start_time->format('Y-m-d'));

        return view('admin.staff.attendance-list', compact('user', 'date', 'calender', 'attendances'));
    }

    public function exportCsv(Request $request)
    {
        $user = User::find($request->user_id);

        // 画面で表示してる期間を受け取り
        // 指定月の開始日と終了日を取得
        $date = Carbon::parse($request->date);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 期間の日付をすべて作成
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        // 期間内の出勤データを取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($a) => $a->date->format('Y-m-d')); // dateをキーにしておく

        // CSV出力
        $response = new StreamedResponse(function () use ($period, $attendances, $user) {
            $handle = fopen('php://output', 'w');

            // Excel文字化け対策
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($handle, [$user->name . 'さんの勤怠一覧']);
            fputcsv($handle, ['日付', '開始時間', '終了時間', '休憩', '合計']);

            // 期間でループ（データない日も出す）
            foreach ($period as $date) {
                $dateStr = $date->locale('ja')->isoFormat('MM/DD(dd)');

                $attendance = $attendances[$date->format('Y-m-d')] ?? null;

                fputcsv($handle, [
                    $dateStr,
                    $attendance?->start_time ? $attendance->start_time->format('H:i') : '',
                    $attendance?->end_time   ? $attendance->end_time->format('H:i')   : '',
                    $attendance?->totalRestTimeAttribute ?? '',
                    $attendance?->totalWorkTimeAttribute ?? '',
                ]);
            }

            fclose($handle);
        });

        $filename = 'attendance_' . $startOfMonth->format('Ymd') . '_' . $endOfMonth->format('Ymd') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

        return $response;
    }

    public function requestList()
    {
        $requests = AttendanceRequest::with('user')->latest()->get();
        return view('admin.stamp_correction_request.list', compact('requests'));
    }

    public function approval(Request $request)
    {
        $requestData = AttendanceRequest::with('restRequests')->findOrFail($request->request_id);

        // === 勤怠テーブルの処理 ===
        if ($requestData->attendance_id) {
            // 既存データ更新
            $attendance = Attendance::findOrFail($requestData->attendance_id);
            $attendance->update([
                'start_time' => $requestData->start_time,
                'end_time'   => $requestData->end_time,
                'notes'      => $requestData->notes,
                'date'       => $requestData->start_time->copy()->startOfDay(),
            ]);

            // 既存休憩を削除して再登録（シンプル）
            AttendanceRest::where('attendance_id', $attendance->id)->delete();
        } else {
            // 新規登録
            $attendance = Attendance::create([
                'user_id'    => $requestData->user_id,
                'start_time' => $requestData->start_time,
                'end_time'   => $requestData->end_time,
                'notes'      => $requestData->notes,
                'date'       => $requestData->start_time->copy()->startOfDay(),
            ]);
        }

        // === 休憩テーブルの処理 ===
        foreach ($requestData->restRequests as $restReq) {
            AttendanceRest::create([
                'attendance_id' => $attendance->id,
                'start_time'    => $restReq->start_time,
                'end_time'      => $restReq->end_time,
            ]);
        }

        // === 申請状態を承認済みに変更 ===
        $requestData->update([
            'state' => 2, // 例：2 = 承認済
            'attendance_id' => $attendance->id, // 紐づけ
        ]);

        return redirect()
            ->route('admin.attendance.detail', ['id' => $attendance->id])
            ->with('success', '勤怠申請を承認しました。');
    }
}
