<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $url = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
            // 認証コード生成（8桁英数字）
            $code = Str::upper(Str::random(8));
            Cache::put('verify_code_' . $notifiable->id, $code, now()->addMinutes(15));

            return (new MailMessage)
                ->subject('【COACHTECH】メールアドレスのご確認')
                ->greeting('こんにちは！')
                ->line('COACHTECHへのご登録ありがとうございます。')
                ->line('以下のいずれかの方法でメール認証を完了してください。')
                ->line('① 認証用リンクをクリック')
                ->action('メールアドレスを確認する', $url) // ボタンは1つだけ
                ->line('② 以下の認証コードを手動で入力')
                ->line('認証コード：**' . $code . '**')
                ->line('手動で入力する場合はこちら：' . url('/email/verify/manual')) // ← テキストリンクで代替
                ->line('※認証コードの有効期限は15分です。');
        });
    }
}
