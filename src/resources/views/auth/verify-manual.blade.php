@extends('layout.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/auth/verify-manual.css')}}">
@endsection

@section('content')
<main class="manual-verify-container">
    <div class="manual-verify-wrapper">
        <h2 class="manual-verify-title">メール認証コードを入力してください</h2>

        <form method="POST" action="{{ route('verification.manual.verify') }}" novalidate>
            @csrf
            <input type="text" name="verify_code" placeholder="認証コードを入力" class="manual-input" required>
            <button type="submit" class="manual-submit">認証する</button>
        </form>

        @if (session('error'))
        <p class="error-message">{{ session('error') }}</p>
        @endif
    </div>
</main>
@endsection