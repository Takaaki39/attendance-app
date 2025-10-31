@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/attendance.css')}}">
@endsection

@section('content')
<main class="attendance-container">
    <h2 class="status-label">{{ $status }}</h2>
    <div class="date">{{ now()->locale('ja')->isoFormat('YYYY年M月D日(dd)') }}</div>
    <div class="time" id="clock">{{ now()->format('H:i') }}</div>

    <div class="button-group">
        @if ($status === '勤務外')
        <form action="{{ route('attendance.start') }}" method="POST">
            @csrf
            <button type="submit" class="btn start-btn">出勤</button>
        </form>

        @elseif ($status === '出勤中')
        <form action="{{ route('attendance.end') }}" method="POST">
            @csrf
            <button type="submit" class="btn end-btn">退勤</button>
        </form>

        <form action="{{ route('attendance.restStart') }}" method="POST">
            @csrf
            <button type="submit" class="btn rest-start-btn">休憩入</button>
        </form>

        @elseif ($status === '休憩中')
        <form action="{{ route('attendance.restEnd') }}" method="POST">
            @csrf
            <button type="submit" class="btn rest-end-btn">休憩戻</button>
        </form>

        @elseif ($status === '退勤済')
        <p class="thanks-message">お疲れ様でした。</p>
        @endif
    </div>
</main>

<script>
    // 現在時刻を1秒ごとに更新
    setInterval(() => {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('clock').textContent = `${h}:${m}`;
    }, 1000);
</script>
@endsection