@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/list.css')}}">
@endsection

@section('content')
<main class="container">
    <h1 class="title">勤怠一覧</h1>

    <div class="date-container">
        <a class="nav-btn" href="?date={{ $date->copy()->subMonth()->toDateString() }}">← 前月</a>
        <div class="date-picker">
            <img src="{{ asset('storage/images/calender.png') }}" alt="カレンダー" class="calendar-icon">
            <input type="text" id="currentDate" value="{{ $date->format('Y/m') }}" readonly>
        </div>
        <a class="nav-btn" href="?date={{ $date->copy()->addMonth()->toDateString() }}">翌月 →</a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($calender as $day)
            @php
            $attendance = $attendances[$day->format('Y-m-d')] ?? null;
            @endphp
            <tr>
                <td>{{ $day->locale('ja')->isoFormat('MM/DD(dd)') }}</td>
                <td>{{ $attendance?->start_time ? $attendance->start_time->format('H:i') : '' }}</td>
                <td>{{ $attendance?->end_time ? $attendance->end_time->format('H:i') : '' }}</td>
                <td>{{ $attendance?->total_rest_time ?? '' }}</td>
                <td>{{ $attendance?->total_work_time ?? '' }}</td>
                @if($attendance)
                <td><a href="{{route('attendance.detail', ['id' => $attendance?->id])}}">詳細</a></td>
                @else
                <td>詳細</td>
                @endif

            </tr>
            @endforeach
        </tbody>
    </table>
</main>
@endsection