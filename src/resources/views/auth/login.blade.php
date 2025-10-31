@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/auth/login.css')}}">
@endsection

@section('content')
<main class="login-container">
    <h1 class="login-title">ログイン</h1>
    <form action="/login" method="post" class="login-form" novalidate>
        @csrf
        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{old('email')}}" required>
            @error('email')
            <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" required>
            @error('password')
            <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="login-btn">ログインする</button>
    </form>
    <p class="register-link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </p>
</main>
@endsection