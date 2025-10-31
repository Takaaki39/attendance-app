<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <link rel="stylesheet" href="{{asset('css/sanitize.css')}}">
    <link rel="stylesheet" href="{{asset('css/common.css')}}">
    @yield('css')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gorditas:wght@400;700&family=Inika:wght@400;700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
</head>

<body>
    <header class="header">
        <!-- 左ロゴ -->
        <div class="header-section header-left">
            <a href="{{ route('attendance.index') }}">
                <img src="{{ asset('storage/images/logo.svg') }}" alt="COACHTECH">
            </a>
        </div>

        <!-- 右側コントロール（子ビューで定義） -->
        <div class="header-right">
            @yield('header-right-general')
        </div>
    </header>

    @if (session('success'))
    <div class="success-message">
        {{ session('success') }}
    </div>
    @endif

    @yield('content')
</body>

</html>