<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRest;
use App\Models\AttendanceRestRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 今日の出勤データ取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('start_time', $today)
            ->latest('start_time')
            ->first();

        return view('attendance.index', compact('attendance'));
    }

    public function start()
    {
        Attendance::create([
            'user_id' => Auth::id(),
            'date'       => now()->toDateString(),
            'start_time' => now(),
        ]);

        return redirect()->route('attendance.index')->with('status', '出勤しました');
    }

    public function end()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($attendance) {
            $attendance->update(['end_time' => Carbon::now()]);
        }

        return redirect()->route('attendance.index')->with('status', '退勤しました');
    }

    public function restStart()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($attendance) {
            AttendanceRest::create([
                'attendance_id' => $attendance->id,
                'start_time' => Carbon::now(),
            ]);
        }

        return redirect()->route('attendance.index')->with('status', '休憩開始');
    }

    // 休憩終了
    public function restEnd()
    {
        $rest = AttendanceRest::whereHas('attendance', function ($q) {
            $q->where('user_id', Auth::id())
                ->whereNull('end_time');
        })
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($rest) {
            $rest->update(['end_time' => Carbon::now()]);
        }

        return redirect()->route('attendance.index')->with('status', '休憩終了');
    }

    public function list(Request $request)
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

        $attendances = Attendance::where('user_id', Auth::id())
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($a) => $a->start_time->format('Y-m-d'));

        return view('attendance.list', compact('date', 'calender', 'attendances'));
    }

    public function detail(Request $request, $id = null)
    {
        $user = Auth::user();
        if ($id) {
            $attendance = Attendance::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            $requestData = AttendanceRequest::where('attendance_id', $attendance->id)
                ->latest()
                ->first();
            if ($requestData != null && $requestData->state == 2) {
                $requestData = null;
            }
        } else if ($request->request_id) {
            $attendance = null;
            $requestData = AttendanceRequest::where('id', $request->request_id)
                ->latest()
                ->first();
        } else {
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

        return view('attendance.detail', compact('user', 'attendance', 'requestData'));
    }

    public function request(ApplicationRequest $request)
    {
        DB::beginTransaction();

        $user = Auth::user();
        $year = intval(str_replace('年', '', $request->year)); // 2025年 → 2025
        $day  = str_replace(['月', '日'], ['-', ''], $request->day); // 3月12日 → 3-12
        $baseDate = Carbon::parse($year . '-' . $day)->startOfDay();

        $parseTime = function ($time) use ($baseDate) {
            if (empty($time)) return null;
            return Carbon::parse($baseDate->format('Y-m-d') . ' ' . $time)->setSeconds(0);
        };

        try {
            // 出勤修正申請の作成
            $attendanceRequest = AttendanceRequest::create([
                'attendance_id' => $request->attendance_id ?: null,
                'user_id'       => $user->id,
                'start_time'    => $parseTime($request->start_time),
                'end_time'      => $parseTime($request->end_time),
                'state'         => 1, // 申請中
                'notes'         => $request->notes ?? '',
                'request_date'  => now(),
            ]);

            // ======== 既存の休憩修正申請の登録 ========
            $restIds    = $request->input('rest_id', []);           // 既存休憩ID
            $restStarts = $request->input('rest_start_time', []);   // 開始時間
            $restEnds   = $request->input('rest_end_time', []);     // 終了時間

            foreach ($restIds as $i => $restId) {
                $start = $restStarts[$i] ?? null;
                $end   = $restEnds[$i] ?? null;

                // 既存の休憩は必ず入力必須（Validation 側で担保済み）
                AttendanceRestRequest::create([
                    'request_id' => $attendanceRequest->id,
                    'rest_id'    => $restId,
                    'start_time' => $parseTime($start),
                    'end_time'   => $parseTime($end),
                ]);
            }

            // ======== 新規追加（空欄を許可） ========
            $newStart = $request->input('new_rest_start_time');
            $newEnd   = $request->input('new_rest_end_time');

            // どちらかが入力されている場合のみ登録（完全空でスキップ）
            if ($newStart || $newEnd) {
                AttendanceRestRequest::create([
                    'request_id' => $attendanceRequest->id,
                    'rest_id'    => null, // 新規休憩なので既存 ID なし
                    'start_time' => $parseTime($newStart),
                    'end_time'   => $parseTime($newEnd),
                ]);
            }

            DB::commit();
            return redirect()->route('attendance.list')->with('success', '勤怠修正申請を送信しました。');
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
            report($e);
            return back()->withErrors(['error' => '申請の送信に失敗しました。']);
        }
    }


    public function requestList()
    {
        $requests = AttendanceRequest::with('user')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
        return view('stamp-correction-request.list', compact('requests'));
    }
}
