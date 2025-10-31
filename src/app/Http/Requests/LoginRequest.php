<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    /**
     * カスタムメッセージ
     */
    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレス形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'ログイン試行回数が多すぎます。しばらくしてから再試行してください。',
        ]);
    }

    public function throttleKey(): string
    {
        return strtolower($this->input('email')) . '|' . $this->ip();
    }

    /**
     * バリデーション後の追加チェック（ログイン情報の正否）
     */
    protected function passedValidation()
    {
        $credentials = $this->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            // 一般的なログインエラーを発生させる
            $this->failedLogin();
        }
    }

    protected function failedLogin()
    {
        // バリデーション例外を発生させる（Laravel標準の仕組み）
        $validator = $this->getValidatorInstance();
        $validator->errors()->add('email', 'ログイン情報が登録されていません');

        throw new \Illuminate\Validation\ValidationException($validator);
    }
}
