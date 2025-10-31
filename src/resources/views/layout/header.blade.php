@section('header-right-general')
<div class="controls">
    <!-- PCで表示するリンク群（横並び） -->
    <div class="header-links">
        @if(Auth::check() && Auth::user()->hasVerifiedEmail())
        <!-- 一般ユーザー -->
        <a class="header-auth" href="/mypage">勤怠</a>
        <a class="header-auth" href="/mypage">勤怠一覧</a>
        <a class="header-auth" href="/mypage">申請</a>
        <form class="header-btn" action="/logout" method="post">
            @csrf
            <button class="header-auth" type="submit">ログアウト</button>
        </form>
        @elseif(Auth::guard('admin')->check())
        <!-- 管理人 -->
        <a class="header-auth" href="/mypage">勤怠一覧</a>
        <a class="header-auth" href="/mypage">スタッフ一覧</a>
        <a class="header-auth" href="/mypage">申請一覧</a>
        <form class="header-btn" action="{{ route('admin.logout') }}" method="post">
            @csrf
            <button class="header-auth" type="submit">ログアウト</button>
        </form>
        @endif
    </div>

    <!-- ハンバーガー（タブレット・スマホ用） -->
    <button class="menu-toggle" id="menu-toggle" aria-expanded="false" aria-controls="header-menu">☰</button>

    <!-- ハンバーガーメニュー（開いたときに表示） -->
    <nav class="header-menu" id="header-menu" aria-hidden="true">
        @if(Auth::check() && Auth::user()->hasVerifiedEmail())
        <!-- 一般ユーザー -->
        <a class="header-auth" href="/mypage">勤怠</a>
        <a class="header-auth" href="/mypage">勤怠一覧</a>
        <a class="header-auth" href="/mypage">申請</a>
        <form class="header-btn" action="/logout" method="post">
            @csrf
            <button class="header-auth" type="submit">ログアウト</button>
        </form>
        @elseif(Auth::guard('admin')->check())
        <!-- 管理人 -->
        <a class="header-auth" href="/mypage">勤怠一覧</a>
        <a class="header-auth" href="/mypage">スタッフ一覧</a>
        <a class="header-auth" href="/mypage">申請一覧</a>
        <form class="header-btn" action="{{ route('admin.logout') }}" method="post">
            @csrf
            <button class="header-auth" type="submit">ログアウト</button>
        </form>
        @endif
    </nav>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('menu-toggle');
        const menu = document.getElementById('header-menu');

        if (!toggle || !menu) return;

        toggle.addEventListener('click', () => {
            menu.classList.toggle('active');
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            menu.setAttribute('aria-hidden', String(expanded));
        });

        // メニュー外クリックで閉じる
        document.addEventListener('click', (e) => {
            if (!menu.classList.contains('active')) return;
            if (menu.contains(e.target) || toggle.contains(e.target)) return;
            menu.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
        });
    });
</script>
@endsection