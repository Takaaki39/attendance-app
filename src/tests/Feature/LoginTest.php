<?php

namespace Tests\Feature;

use App\Models\Admin;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一般ユーザー：メールアドレス未入力
     */
    public function test_user_login_email_required()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 一般ユーザー：パスワード未入力
     */
    public function test_user_login_password_required()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 一般ユーザー：登録情報と一致しない
     */
    public function test_user_login_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }


    // ----------------------------------------
    // 管理者ログイン（/admin/login）
    // ----------------------------------------

    /**
     * 管理者：メールアドレス未入力
     */
    public function test_admin_login_email_required()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 管理者：パスワード未入力
     */
    public function test_admin_login_password_required()
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 管理者：登録情報と一致しない
     */
    public function test_admin_login_invalid_credentials()
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
