<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function list(Request $request)
    {
        // URLやフォームから受け取った日付（なければ今日）
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now();

        $attendances = Attendance::whereDate('start_time', $date->toDateString())->get();

        return view('admin.attendance.list', compact('date', 'attendances'));
    }

    public function detail()
    {
        return view('admin.attendance.detail');
    }
}
