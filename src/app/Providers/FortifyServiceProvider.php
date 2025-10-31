<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\RegisterResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Laravel\Fortify\Http\Requests\LoginRequest::class,
            \App\Http\Requests\LoginRequest::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return request()->is('admin/*')
                ? view('admin.login')
                : view('auth.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        // ユーザー登録後のリダイレクト先を指定
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                // メール認証へリダイレクト
                return redirect()->route('verification.notice');
            }
        });

        // ログイン認証処理をカスタマイズ
        Fortify::authenticateUsing(function ($request) {
            // 管理者ログインページからのリクエストならadminガードを使用
            if ($request->is('admin/*')) {
                $admin = \App\Models\Admin::where('email', $request->email)->first();
                if ($admin && Hash::check($request->password, $admin->password)) {
                    Auth::guard('admin')->login($admin);
                    return $admin;
                }
                return null;
            }

            // 一般ユーザー用
            $user = User::where('email', $request->email)->first();

            if (
                $user &&
                Hash::check($request->password, $user->password)
            ) {

                // メール未認証の場合はログインさせない
                if (is_null($user->email_verified_at)) {
                    throw ValidationException::withMessages([
                        Fortify::username() => __('auth.unverified'),
                    ]);
                }

                return $user;
            }

            return null;
        });

        /**
         * 🔹 ログイン後のリダイレクト先を動的に変更
         */
        Fortify::redirects('login', function () {
            return request()->is('admin/*')
                ? RouteServiceProvider::ADMIN_HOME
                : RouteServiceProvider::HOME;
        });
    }
}
