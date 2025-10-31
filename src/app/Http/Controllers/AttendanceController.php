<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $status = '勤務外';
        $resting = false;

        if ($attendance) {
            if ($attendance->end_time) {
                $status = '退勤済';
            } else {
                // 出勤中（退勤していない）
                $status = '出勤中';

                // 現在進行中の休憩があるか確認
                $rest = AttendanceRest::where('attendance_id', $attendance->id)
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();

                if ($rest) {
                    $status = '休憩中';
                    $resting = true;
                }
            }
        }

        return view('attendance.index', compact('attendance', 'status', 'resting'));
    }

    public function start(Request $request)
    {
        Attendance::create([
            'user_id' => Auth::id(),
            'start_time' => Carbon::now(),
        ]);

        return redirect()->route('attendance.index')->with('status', '出勤しました');
    }

    public function end(Request $request)
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

    public function restStart(Request $request)
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
    public function restEnd(Request $request)
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
}
