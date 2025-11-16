@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/list.css')}}">
@endsection

@section('content')
<main class="container">
    <h1 class="title">{{ $user->name }}さんの勤怠一覧</h1>

    <div class="date-container">
        <a class="nav-btn" href="?date={{ $date->copy()->subMonth()->toDateString() }}">← 前月</a>
        <div class="date-picker">
            <img src="{{ asset('storage/images/calender.png') }}" alt="カレンダー" class="calendar-icon">
            <input type="text" id="currentDate" value="{{ $date->format('Y/m') }}" readonly>
        </div>
        <a class="nav-btn" href="?date={{ $date->copy()->addMonth()->toDateString() }}">翌月 →</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
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
                    <td><a href="{{route('admin.attendance.detail', ['id' => $attendance?->id])}}">詳細</a></td>
                    @else
                    <td><a href="{{route('admin.attendance.detail', ['user_id' => $user->id, 'date' => $day->format('Y-m-d')])}}">詳細</a></td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        <form action="{{ route('admin.attendance.exportCsv') }}" method="POST">
            @csrf

            {{-- 画面で表示してる期間や検索条件をそのまま送る --}}
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="date" value="{{ $date }}">

            <button type="submit" class="output-button">
                CSV出力
            </button>
        </form>
    </div>
</main>
@endsection