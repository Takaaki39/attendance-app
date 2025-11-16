<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ---------------------------------------------------
// 一般ユーザー用ログイン関連
// ---------------------------------------------------
Route::get('/login', [AuthController::class, 'login'])->middleware('guest')->name('login');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');
    Route::post('/attendance/restStart', [AttendanceController::class, 'restStart'])->name('attendance.restStart');
    Route::post('/attendance/restEnd', [AttendanceController::class, 'restEnd'])->name('attendance.restEnd');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id?}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/request', [AttendanceController::class, 'request'])->name('attendance.request');
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestList'])->name('stamp_correction_request.list');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ---------------------------------------------------
// メール認証
// ---------------------------------------------------
Route::prefix('email')->group(function () {
    // メール内のURLでの認証（Fortify標準）
    Route::get('/verify/{id}/{hash}', [AuthController::class, 'auth'])
        ->middleware(['auth', 'signed'])
        ->name('verification.verify');

    // メール認証待ち画面
    Route::get('/verify', [AuthController::class, 'wait'])
        ->middleware('auth')
        ->name('verification.notice');

    // 認証メール再送
    Route::post('/verification-notification', [AuthController::class, 'resending'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('verification.send');

    // 手動コード入力ページ
    Route::get('/verify/manual', [AuthController::class, 'show'])
        ->middleware('auth')
        ->name('verification.manual');

    // コード入力フォーム送信処理
    Route::post('/verify/manual', [AuthController::class, 'verify'])
        ->middleware('auth')
        ->name('verification.manual.verify');
});

// ---------------------------------------------------
// 管理者専用ページ（別ファイルに分離）
// ---------------------------------------------------
require __DIR__ . '/admin.php';
