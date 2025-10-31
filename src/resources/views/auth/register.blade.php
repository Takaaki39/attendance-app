@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/auth/register.css')}}">
@endsection

@section('content')
<main class="container">
    <h1>会員登録</h1>
    <form action="/register" method="post" class="register-form" novalidate>
        @csrf
        <div class="form-group">
            <label for="username">ユーザー名</label>
            <input type="text" id="username" name="name" value="{{old('name')}}" required>
            @error('name')
            <div class="error">{{ $message }}</div>
            @enderror
        </div>

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

        <div class="form-group">
            <label for="password_confirmation">確認用パスワード</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
            @error('password_confirmation')
            <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-submit">登録する</button>
    </form>

    <p class="login-link">
        <a href="{{ route('login') }}">ログインはこちら</a>
    </p>
</main>
@endsection