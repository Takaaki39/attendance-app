@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/list.css')}}">
@endsection

@section('content')
<main class="container">
    <h1 class="title">申請一覧</h1>

    <div class="tab-menu">
        <button class="tab active" onclick="filterState(1)">承認待ち</button>
        <button class="tab" onclick="filterState(2)">承認済み</button>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($requests as $request)
            <tr data-state="{{ $request->state }}">
                <td>{{ $request->state_label }}</td>
                <td>{{ $request->user->name }}</td>
                <td>{{ $request->start_time->format('Y/m/d') }}</td>
                <td class="limited-cell">{{ $request->notes }}</td>
                <td>{{ $request->request_date->format('Y/m/d') }}</td>
                <td><a href="{{route('attendance.detail', ['request_id' => $request->id])}}">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</main>

<script>
    function filterState(state) {
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.dataset.state == state ? '' : 'none';
        });

        document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
        document.querySelector(`.tab[onclick="filterState(${state})"]`).classList.add('active');
    }
    document.addEventListener('DOMContentLoaded', () => {
        filterState(1); // 初期は承認待ちのみ表示
    });
</script>
@endsection