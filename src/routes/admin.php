<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------
// 管理者ログインページ（未ログインでもアクセス可）
// ---------------------------------------------------
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'loginView'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'login'])->name('admin.login.post');
});

// ---------------------------------------------------
// 管理者ログイン後ページ（ログイン必須）
// ---------------------------------------------------
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', function () {
        return view('admin.attendance.list');
    })->name('admin.attendance.list');

    // ここに管理者専用ページを追加していく
    Route::get('/users', function () {
        return 'ユーザー管理ページ';
    })->name('admin.users');


    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
});
