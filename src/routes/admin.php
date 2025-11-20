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
    Route::get('/attendance/{id?}', [AdminController::class, 'detail'])->name('admin.attendance.detail');
    Route::post('/attendance/request', [AdminController::class, 'requestFix'])->name('admin.attendance.request');
    Route::post('/attendance/approval', [AdminController::class, 'approval'])->name('admin.attendance.approval');
    Route::get('/staff/list', [AdminController::class, 'staffList'])->name('admin.staff.list');
    Route::get('/attendance/staff/{id}', [AdminController::class, 'staff'])->name('admin.attendance.staff');
    Route::post('/attendance/export-csv', [AdminController::class, 'exportCsv'])->name('admin.attendance.exportCsv');
    Route::get('/stamp_correction_request/list', [AdminController::class, 'requestList'])->name('admin.stamp_correction_request.list');

    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});
