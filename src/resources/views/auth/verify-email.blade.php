@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/auth/verify-email.css')}}">
@endsection

@section('content')
<main class="verify-container">
    <div class="verify-wrapper">
        <h2 class="verify-message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </h2>

        <a href="{{ route('verification.manual') }}" class="verify-button">認証はこちらから</a>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="resend-link">認証メールを再送する</button>
        </form>
    </div>
</main>
@endsection