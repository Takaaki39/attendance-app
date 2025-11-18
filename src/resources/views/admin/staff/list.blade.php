@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/list.css')}}">
@endsection

@section('content')
<main class="container">
    <h1 class="title">スタッフ一覧</h1>

    <table class="data-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td><a href="{{route('admin.attendance.staff',  ['id' => $user->id])}}">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</main>
@endsection