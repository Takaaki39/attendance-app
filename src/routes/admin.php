<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------
// 管理者ログインページ（未ログインでもアクセス可）
// ---------------------------------------------------
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'loginView'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
});

// ---------------------------------------------------
// 管理者ログイン後ページ（ログイン必須）
// ---------------------------------------------------
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminController::class, 'list'])->name('admin.attendance.list');
    Route::get('/attendance/{id}', [AdminController::class, 'detail'])->name('admin.attendance.detail');

    // ここに管理者専用ページを追加していく
    Route::get('/users', function () {
        return 'ユーザー管理ページ';
    })->name('admin.users');


    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});
