@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/list.css')}}">
@endsection

@section('content')
<main class="container">
    <h1 class="title">{{$date->format('Y年m月d日')}}の勤怠</h1>

    <div class="date-container">
        <a class="nav-btn" href="?date={{ $date->copy()->subDay()->toDateString() }}">← 前日</a>
        <div class="date-picker">
            <img src="{{ asset('storage/images/calender.png') }}" alt="カレンダー" class="calendar-icon">
            <input type="date" id="currentDate" value="{{$date->format('Y-m-d')}}">
        </div>
        <a class="nav-btn" href="?date={{ $date->copy()->addDay()->toDateString() }}">翌日 →</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            @php
            // 指定日の勤怠を取得
            $attendance = $user->attendanceOnDate($date);
            @endphp
            <tr>
                <td>{{$user->name}}</td>
                <td>{{$attendance?->start_time?->format('H:i')}}</td>
                <td>{{$attendance?->end_time?->format('H:i')}}</td>
                <td>{{$attendance?->total_rest_time }}</td>
                <td>{{$attendance?->total_work_time }}</td>
                <td>
                    @if($attendance)
                    <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}">詳細</a>
                    @else
                    <a href="{{ route('admin.attendance.detail', ['user_id' => $user->id]) }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</main>
@endsection