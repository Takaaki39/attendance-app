<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{
    public function auth(EmailVerificationRequest $request)
    {
        $request->fulfill(); // メールアドレス認証完了
        return redirect(RouteServiceProvider::HOME)->with('success', 'メール認証が完了しました！');
    }

    public function wait()
    {
        return view('auth.verify-email');
    }

    public function resending(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return redirect('/email/verify')->with('success', '認証メールを再送しました！');
    }

    public function show()
    {
        return view('auth.verify-manual');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'verify_code' => 'required|string'
        ]);

        $user = Auth::user();
        $savedCode = Cache::get('verify_code_' . $user->id);

        if ($savedCode && $savedCode === $request->verify_code) {
            $user->markEmailAsVerified();
            Cache::forget('verify_code_' . $user->id);
            return redirect(RouteServiceProvider::HOME)->with('success', 'メール認証が完了しました！');
        }

        return back()->with('error', '認証コードが正しくありません。');
    }
}
