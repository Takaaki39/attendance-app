<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレス形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }

    /**
     * ログイン試行回数制限
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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
}
