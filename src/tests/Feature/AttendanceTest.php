<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsVerifiedUser()
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        return $user;
    }

    /**
     * 管理者ログイン
     */
    private function actingAsAdmin()
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * 現在の日時が画面に表示されるか
     */
    public function test_display_current_datetime()
    {
        $user = $this->actingAsVerifiedUser();

        Carbon::setTestNow(Carbon::now());
        $now = now()->format('Y年m月d日');

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee($now);
    }

    /**
     * 勤務外ステータス表示
     */
    public function test_status_off_duty()
    {
        $user = $this->actingAsVerifiedUser();

        $response = $this->get('/attendance');

        $response->assertSee('勤務外');
    }

    /**
     * 出勤中ステータス表示
     */
    public function test_status_start()
    {
        $user = $this->actingAsVerifiedUser();

        $response = $this->followingRedirects()
            ->post(route('attendance.start'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中ステータス表示
     */
    public function test_status_rest_start()
    {
        $user = $this->actingAsVerifiedUser();

        // 出勤中レコード
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time'   => now(),
            'end_time'     => null,
        ]);

        $response = $this->followingRedirects()
            ->post(route('attendance.restStart'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済ステータス表示
     */
    public function test_status_end()
    {
        $user = $this->actingAsVerifiedUser();

        // 先に出勤レコードを作っておく
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time'   => now(),
            'end_time'     => null,
        ]);

        $response = $this->followingRedirects()
            ->post(route('attendance.end'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    /**
     * 出勤ボタンが正しく機能する
     */
    public function test_user_can_start_work()
    {
        $this->actingAsVerifiedUser();

        $response = $this->get('/attendance');
        $response->assertSee('出勤');

        $response = $this->followingRedirects()->post('/attendance/start');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 出勤は一日一回のみで、退勤済みユーザーには出勤ボタンが表示されない
     */
    public function test_user_cannot_start_work_twice()
    {
        $this->actingAsVerifiedUser();
        $this->followingRedirects()->post('/attendance/start');
        $this->followingRedirects()->post('/attendance/end');

        $response = $this->get('/attendance');
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧に正しく記録される
     */
    public function test_attendance_start_time_is_recorded()
    {
        $user = $this->actingAsVerifiedUser();

        // 出勤処理
        $response = $this->post('/attendance/start');

        // レスポンスが正しいステータスで返る
        $response->assertStatus(302);

        // attendances に登録された最新データを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        // 必ずデータが存在する
        $this->assertNotNull($attendance, '出勤データが登録されていません。');

        // 現在時刻（出勤処理を行う直前に取得）
        $now = now();
        $expectedTime = $now->format('H:i');

        // start_time（DBに記録された時刻）が現在時刻と一致するか確認
        $this->assertEquals(
            $expectedTime,
            Carbon::parse($attendance->start_time)->format('H:i'),
            '出勤時刻が一致しません。'
        );
    }

    /**
     * 休憩ボタンが表示され、休憩後にステータスが休憩中に変わることを確認する
     */
    public function test_user_can_start_break()
    {
        $this->actingAsVerifiedUser();

        $this->get('/attendance');
        // 出勤処理
        $response = $this->followingRedirects()->post('/attendance/start');
        $response->assertSee('休憩入');

        $response = $this->followingRedirects()->post('/attendance/restStart');
        $response->assertSee('休憩中');
    }

    /**
     * 休憩は一日何回でもできる
     */
    public function test_user_can_take_multiple_breaks()
    {
        $this->actingAsVerifiedUser();

        $this->get('/attendance');
        // 出勤処理
        $this->followingRedirects()->post('/attendance/start');
        // 休憩入
        $this->post('/attendance/restStart');

        // 休憩戻
        $this->post('/attendance/restEnd');

        // 再度休憩入が可能
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが表示され、処理後にステータスが勤務中に戻ることを確認する
     */
    public function test_user_can_end_break()
    {
        $this->actingAsVerifiedUser();

        $this->get('/attendance');
        // 出勤処理
        $this->followingRedirects()->post('/attendance/start');
        // 休憩入り
        $response = $this->followingRedirects()->post('/attendance/restStart');
        $response->assertSee('休憩戻');

        // 休憩戻
        $response = $this->followingRedirects()->post('/attendance/restEnd');
        $response->assertSee('出勤中');
    }

    /**
     * 休憩は一日何回でもできる
     */
    public function test_user_can_take_multiple_breaks2()
    {
        $this->actingAsVerifiedUser();

        $this->get('/attendance');
        // 出勤処理
        $this->followingRedirects()->post('/attendance/start');
        // 休憩入
        $this->post('/attendance/restStart');

        // 休憩戻
        $this->post('/attendance/restEnd');

        // 再休憩入
        $response = $this->followingRedirects()->post('/attendance/restStart');
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧に正しく記録される
     */
    public function test_break_times_are_recorded_in_attendance_list()
    {
        $user = $this->actingAsVerifiedUser();

        // 出勤
        $this->post('/attendance/start');

        // --- 休憩入 ---
        $restStart = now(); // 現在時刻
        Carbon::setTestNow($restStart);
        $this->post('/attendance/restStart');

        // --- 休憩戻（1時間後に固定） ---
        $restEnd = $restStart->clone()->addHour();
        Carbon::setTestNow($restEnd);
        $this->post('/attendance/restEnd');

        // 時刻固定解除
        Carbon::setTestNow();

        // 今日の日付
        $today = $restStart->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // attendance_rests の今日の合計休憩時間（秒）
        $totalRestSecondsFromDb = AttendanceRest::where('attendance_id', $attendance->id)
            ->get()
            ->sum(function ($rest) {
                return Carbon::parse($rest->end_time)
                    ->diffInSeconds(Carbon::parse($rest->start_time));
            });

        // 手計算：期待値（1時間 = 3600 秒）
        $expectedSeconds = $restEnd->diffInSeconds($restStart);

        // Assert: DB の合計休憩時間が期待値と一致する
        $this->assertEquals(
            $expectedSeconds,
            $totalRestSecondsFromDb,
            "DB に保存された休憩時間（{$totalRestSecondsFromDb}秒）が期待値（{$expectedSeconds}秒）と一致しません"
        );
    }

    /**
     * 退勤ボタンが表示され、退勤後にステータスが退勤済に変わることを確認する
     */
    public function test_user_can_end_work()
    {
        $user = $this->actingAsVerifiedUser();

        $this->get('/attendance');
        // 出勤処理
        $response = $this->followingRedirects()->post('/attendance/start');
        $response->assertSee('退勤');

        $response = $this->followingRedirects()->post('/attendance/end');
        $response->assertSee('退勤済');
    }

    /**
     * 退勤時刻が勤怠一覧に記録されることを確認する
     */
    public function test_attendance_end_time_is_recorded()
    {
        $user = $this->actingAsVerifiedUser();

        // 出勤処理
        $this->post('/attendance/start');
        $response = $this->followingRedirects()->post('/attendance/end');

        // attendances に登録された最新データを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        // 必ずデータが存在する
        $this->assertNotNull($attendance, '出勤データが登録されていません。');

        // 現在時刻（出勤処理を行う直前に取得）
        $now = now();
        $expectedTime = $now->format('H:i');

        // start_time（DBに記録された時刻）が現在時刻と一致するか確認
        $this->assertEquals(
            $expectedTime,
            Carbon::parse($attendance->end_time)->format('H:i'),
            '出勤時刻が一致しません。'
        );
    }

    /**
     * 勤怠一覧：自分の勤怠情報がすべて表示される
     */
    public function test_user_attendance_list_displays_all_own_records()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(1)->toDateString(),
            'start_time' => now()->subDays(1)->setTime(9, 0),
            'end_time' => now()->subDays(1)->setTime(18, 0),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->setTime(9, 30),
            'end_time' => now()->setTime(18, 30),
        ]);

        $response = $this->get(route('attendance.list'));

        $response->assertSee(Carbon::parse($attendance1->date)
            ->locale('ja')
            ->isoFormat('MM/DD(dd)'));
        $response->assertSee(Carbon::parse($attendance1->start_time)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance1->end_time)->format('H:i'));
        $response->assertSee($attendance1->total_rest_time);
        $response->assertSee($attendance1->total_work_time);

        $response->assertSee(Carbon::parse($attendance2->date)
            ->locale('ja')
            ->isoFormat('MM/DD(dd)'));
        $response->assertSee(Carbon::parse($attendance2->start_time)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance2->end_time)->format('H:i'));
        $response->assertSee($attendance2->total_rest_time);
        $response->assertSee($attendance2->total_work_time);
    }

    /**
     * 勤怠一覧：遷移時に現在の月が表示される
     */
    public function test_attendance_list_shows_current_month()
    {
        $this->actingAsVerifiedUser();

        $month = now()->format('Y-m');

        $response = $this->get(route('attendance.list'));

        $response->assertSee($month);
    }

    /**
     * 勤怠一覧：前月ボタンを押すと前月の情報が表示される
     */
    public function test_attendance_list_shows_previous_month()
    {
        $user = $this->actingAsVerifiedUser();

        $previousMonth = now()->subMonth()->format('Y/m');
        $dateQuery = now()->subMonth()->toDateString();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subMonth()->setDay(5)->toDateString(),
            'start_time' => now()->subMonth()->setDay(5)->setTime(9, 0),
        ]);

        $response = $this->get("/attendance/list?date={$dateQuery}");

        $response->assertSee($previousMonth);
        $response->assertSee(Carbon::parse($attendance->start_time)->format('H:i'));
    }

    /**
     * 勤怠一覧：翌月ボタンを押すと翌月の情報が表示される
     */
    public function test_attendance_list_shows_next_month()
    {
        $user = $this->actingAsVerifiedUser();

        $nextMonth = now()->addMonth()->format('Y-m');
        $dateQuery = now()->addMonth()->toDateString();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addMonth()->setDay(10)->toDateString(),
            'start_time' => now()->addMonth()->setDay(10)->setTime(10, 0),
        ]);

        $response = $this->get("/attendance/list?date={$dateQuery}");

        $response->assertSee($nextMonth);
        $response->assertSee(Carbon::parse($attendance->start_time)->format('H:i'));
    }

    /**
     * 勤怠一覧：「詳細」ボタンから詳細画面に遷移できる
     */
    public function test_attendance_list_navigates_to_detail_page()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

        $response = $this->get(route('attendance.list'));

        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);

        $this->get($detailUrl);
        $response->assertStatus(200);
    }

    /**
     * 勤怠詳細：名前がログインユーザー名になっている
     */
    public function test_attendance_detail_shows_user_name()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertSee($user->name);
    }

    /**
     * 勤怠詳細：日付が選択したもので表示されている
     */
    public function test_attendance_detail_shows_correct_date()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-01-10',
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertSee(Carbon::parse($attendance->date)
            ->locale('ja')
            ->isoFormat('Y年'));
        $response->assertSee(Carbon::parse($attendance->date)
            ->locale('ja')
            ->isoFormat('M月D日'));
    }

    /**
     * 勤怠詳細：出勤と退勤が正しい値で表示されている
     */
    public function test_attendance_detail_shows_start_and_end_times()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => now()->setTime(9, 0),
            'end_time' => now()->setTime(18, 0),
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertSee($attendance->start_time->format('H:i'));
        $response->assertSee($attendance->end_time->format('H:i'));
    }

    /**
     * 勤怠詳細：休憩時間が正しく表示されている
     */
    public function test_attendance_detail_shows_rest_times()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $rest = AttendanceRest::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => now()->setTime(12, 0),
            'end_time' => now()->setTime(13, 0),
        ]);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertSee($rest->start_time->format('H:i'));
        $response->assertSee($rest->end_time->format('H:i'));
    }

    /**
     * 出勤時間が退勤時間より後 → エラー
     */
    public function test_start_time_after_end_time_shows_error()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '09:00',
            'end_time'   => '18:00'
        ]);

        $response = $this->post("/attendance/request", [
            'year'          => '2025年',
            'day'           => '11月05日',
            'attendance_id' => $attendance->id,
            'start_time'    => '19:00',
            'end_time'      => '18:00',
            'notes'          => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'start_time' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 休憩開始が退勤より後 → エラー
     */
    public function test_rest_start_after_end_time_shows_error()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '09:00',
            'end_time'   => '18:00'
        ]);

        $response = $this->post("/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'attendance_id'         => $attendance->id,
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'new_rest_start_time'   => '19:00',
            'notes'                  => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'new_rest_start_time' => '休憩時間が不適切な値です'
        ]);
    }

    /**
     * 休憩終了が退勤より後 → エラー
     */
    public function test_rest_end_after_end_time_shows_error()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '09:00',
            'end_time'   => '18:00'
        ]);

        $response = $this->post("/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'attendance_id'         => $attendance->id,
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'new_rest_start_time'   => '17:00',
            'new_rest_end_time'     => '19:00',
            'notes'                  => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'new_rest_end_time' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 備考未入力 → エラー
     */
    public function test_note_is_required()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'start_time' => '09:00',
            'end_time'   => '18:00'
        ]);

        $response = $this->followingRedirects()->post("/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'attendance_id'         => $attendance->id,
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'new_rest_start_time'   => '12:00',
            'new_rest_end_time'     => '13:00',
            'notes'                 => '',
        ]);

        $response->assertSessionHasErrors([
            'notes' => '備考を記入してください'
        ]);
    }

    /**
     * 修正申請処理が実行される（管理者の画面で確認できる）
     */
    public function test_fix_request_is_created()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id'       => $user->id,
            'date'          => now()->toDateString(),
            'start_time'    => '09:00',
            'end_time'      => '18:00'
        ]);

        $response = $this->post("/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'attendance_id'         => $attendance->id,
            'start_time'            => '08:00',
            'end_time'              => '17:00',
            'new_rest_start_time'   => '12:00',
            'new_rest_end_time'     => '13:00',
            'notes'                  => '修正願います',
        ]);
        $response->assertRedirect();

        $request = AttendanceRequest::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($request, '修正申請データが作成されていません');

        $admin = Admin::factory()->create([
            'email' => 'wrong@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/admin/attendance/list');

        $response = $this->get(route('admin.stamp_correction_request.list'));
        $response->assertSee($request->user->name);
        $response->assertSee($request->start_time->format('Y/m/d'));
        $response->assertSee('修正願います');
        $response->assertSee($request->request_date->format('Y/m/d'));

        $response = $this->get("/attendance/detail/{$attendance->id}?request_id={$request->id}");
        $response->assertSee($request->user->name);
        $response->assertSee('08:00');
        $response->assertSee('17:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('修正願います');
    }

    /**
     * 「承認待ち」に自分の申請が全て表示される
     */
    public function test_pending_requests_are_displayed_for_user()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        // 修正申請作成（承認待ち）
        $response = $this->post("/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'attendance_id'         => $attendance->id,
            'start_time'            => '08:00',
            'end_time'              => '17:00',
            'new_rest_start_time'   => '12:00',
            'new_rest_end_time'     => '13:00',
            'notes'                  => '修正願います',
        ]);
        $response->assertRedirect();

        $request = AttendanceRequest::where('attendance_id', $attendance->id)->first();

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee($request->user->name);
        $response->assertSee($request->request_date->format('Y/m/d'));
        $response->assertSee($request->notes);
        $response->assertSee($request->start_time->format('Y/m/d'));
    }

    /**
     * 管理者が承認した申請が「承認済み」に表示される
     */
    public function test_approved_requests_are_displayed_for_user()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        // 承認済み申請
        $response = $this->post("/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'attendance_id'         => $attendance->id,
            'start_time'            => '08:00',
            'end_time'              => '17:00',
            'new_rest_start_time'   => '12:00',
            'new_rest_end_time'     => '13:00',
            'notes'                  => '修正願います',
        ]);
        $response->assertRedirect();

        $request = AttendanceRequest::where('attendance_id', $attendance->id)->first();
        $request->update(['state' => 2]);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee($request->user->name);
        $response->assertSee($request->request_date->format('Y/m/d'));
        $response->assertSee($request->notes);
        $response->assertSee($request->start_time->format('Y/m/d'));
        $response->assertSee("承認済み");
    }

    /**
     * 「詳細」押下で勤怠詳細画面へ遷移
     */
    public function test_request_detail_button_redirects_to_attendance_detail()
    {
        $user = $this->actingAsVerifiedUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->post("/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'attendance_id'         => $attendance->id,
            'start_time'            => '08:00',
            'end_time'              => '17:00',
            'new_rest_start_time'   => '12:00',
            'new_rest_end_time'     => '13:00',
            'notes'                  => '修正願います',
        ]);
        $response->assertRedirect();

        $request = AttendanceRequest::where('attendance_id', $attendance->id)->first();

        $response = $this->get('/stamp_correction_request/list');

        // 実際の遷移確認
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee($request->user->name);
        $response->assertSee($request->start_time->format('H:i'));
        $response->assertSee($request->end_time->format('H:i'));
        $response->assertSee($request->total_rest_time);
        $response->assertSee($request->total_work_time);
        $response->assertSee('修正願います');
    }

    /* --------------------------------------------------------------
        勤怠一覧情報取得機能（管理者）
    -------------------------------------------------------------- */

    /**
     * 管理者はその日の全ユーザーの勤怠情報を確認できる
     */
    public function test_admin_can_see_all_attendance_of_day()
    {
        $admin = $this->actingAsAdmin();

        $attendance1 = Attendance::factory()->create();
        $attendance2 = Attendance::factory()->create();

        $today = now()->toDateString();

        $response = $this->get('/admin/attendance/list?date=' . $today);

        $response->assertStatus(200);
        $response->assertSee($attendance1->user->name);
        $response->assertSee($attendance2->user->name);
    }

    /**
     * 遷移時に現在日付が表示される
     */
    public function test_admin_attendance_list_shows_today_date()
    {
        $admin = $this->actingAsAdmin();

        $today = now()->format('Y-m-d');

        $response = $this->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($today);
    }

    /**
     * 前日ボタンで前日の勤怠情報が表示される
     */
    public function test_admin_can_view_previous_day_attendance()
    {
        $admin = $this->actingAsAdmin();

        $yesterday = now()->subDay()->format('Y-m-d');

        Attendance::factory()->create([
            'date' => $yesterday
        ]);

        $response = $this->get('/admin/attendance/list?date=' . $yesterday);

        $response->assertStatus(200);
        $response->assertSee($yesterday);
    }

    /**
     * 翌日ボタンで次の日の勤怠情報が表示される
     */
    public function test_admin_can_view_next_day_attendance()
    {
        $admin = $this->actingAsAdmin();

        $tomorrow = now()->addDay()->format('Y-m-d');

        Attendance::factory()->create([
            'date' => $tomorrow
        ]);

        $response = $this->get('/admin/attendance/list?date=' . $tomorrow);

        $response->assertStatus(200);
        $response->assertSee($tomorrow);
    }

    /**------------------------------------
     * 勤怠詳細情報取得・修正機能（管理者）
     * ------------------------------------*/
    /**
     * 勤怠詳細画面に選択したデータが表示される（管理者）
     */
    public function test_admin_can_view_correct_attendance_detail()
    {
        $admin = $this->actingAsAdmin();

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'date'       => '2025-01-15',
            'start_time' => '09:00:00',
            'end_time'   => '18:00:00',
            'notes'      => 'テスト備考',
        ]);

        AttendanceRest::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time'    => '12:00:00',
            'end_time'      => '13:00:00',
        ]);

        $response = $this->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('2025年');
        $response->assertSee('1月15日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('テスト備考');
    }

    /**
     * 出勤時間が退勤時間より後 → バリデーションエラー
     */
    public function test_admin_start_time_validation_error()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'start_time' => '09:00:00',
            'end_time'   => '18:00:00',
        ]);

        $this->get("/admin/attendance/{$attendance->id}");
        $response = $this->followingRedirects()->post("/admin/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'user_id'               => $attendance->user_id,
            'attendance_id'         => $attendance->id,
            'start_time'            => '20:00',
            'end_time'              => '17:00',
            'new_rest_start_time'   => '12:00',
            'new_rest_end_time'     => '13:00',
            'notes'                 => '修正しました',
        ]);

        $response->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 休憩開始時間が退勤時間より後 → バリデーションエラー
     */
    public function test_admin_rest_start_time_validation_error()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'start_time' => '09:00:00',
            'end_time'   => '18:00:00',
        ]);

        $this->get("/admin/attendance/{$attendance->id}");
        $response = $this->followingRedirects()->post("/admin/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'user_id'               => $attendance->user_id,
            'attendance_id'         => $attendance->id,
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'new_rest_start_time'   => '19:00',
            'new_rest_end_time'     => '19:30',
            'notes'                 => '修正',
        ]);

        $response->assertSee('休憩時間が不適切な値です');
    }

    /**
     * 休憩終了時間が退勤時間より後 → バリデーションエラー
     */
    public function test_admin_rest_end_time_validation_error()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'start_time' => '09:00',
            'end_time'   => '18:00',
        ]);

        $this->get("/admin/attendance/{$attendance->id}");
        $response = $this->followingRedirects()->post("/admin/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'user_id'               => $attendance->user_id,
            'attendance_id'         => $attendance->id,
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'new_rest_start_time'   => '12:00',
            'new_rest_end_time'     => '20:00',
            'notes'                 => '修正',
        ]);

        $response->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 備考未入力 → バリデーションエラー
     */
    public function test_admin_note_is_required()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create();

        $this->get("/admin/attendance/{$attendance->id}");
        $response = $this->followingRedirects()->post("/admin/attendance/request", [
            'year'                  => '2025年',
            'day'                   => '11月05日',
            'user_id'               => $attendance->user_id,
            'attendance_id'         => $attendance->id,
            'start_time'            => '09:00',
            'end_time'              => '18:00',
            'notes'                 => '',
        ]);

        $response->assertSee('備考を記入してください');
    }

    /**------------------------------------
     * ユーザー情報取得機能（管理者）
     * ------------------------------------*/
    /**
     * 管理者ユーザーが全一般ユーザーの
     * 氏名・メールアドレスを確認できること
     */
    public function test_admin_can_view_all_users()
    {
        $this->actingAsAdmin();

        $users = User::factory()->count(3)->create();

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /**
     * 管理者ユーザーが選択したユーザーの
     * 勤怠情報一覧を正しく確認できること
     */
    public function test_admin_can_view_specific_user_attendances()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $attendances = [];
        for ($i = 0; $i < 3; $i++) {
            $date = now()->startOfMonth()->setDay(rand(1, now()->daysInMonth));
            $start = (clone $date)->setTime(rand(8, 10), rand(0, 59));
            $end = (clone $start)->modify('+' . rand(6, 10) . ' hours');
            $attendances[] = Attendance::factory()->create([
                'user_id'       => $user->id,
                'date'          => $date,
                'start_time'    => $start,
                'end_time'      => $end,
            ]);
        }

        $response = $this->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $response->assertSee(Carbon::parse($attendance->date)->isoFormat('MM/DD(dd)'));
            $response->assertSee(Carbon::parse($attendance->start_time)->format('H:i'));
            $response->assertSee(Carbon::parse($attendance->end_time)->format('H:i'));
            $response->assertSee($attendance->total_rest_time);
            $response->assertSee($attendance->total_work_time);
        }
    }

    /**
     * 表示月より前月ボタンを押下したとき
     * 前月の勤怠情報が表示されること
     */
    public function test_admin_can_view_last_month_attendances()
    {
        $this->actingAsAdmin();

        $user = User::factory()->create();

        $lastMonth = now()->subMonth()->startOfMonth();

        $attendances = [];
        for ($i = 0; $i < 3; $i++) {
            $date = $lastMonth->copy()->setDay(rand(1, now()->daysInMonth));
            $start = (clone $date)->setTime(rand(8, 10), rand(0, 59));
            $end = (clone $start)->modify('+' . rand(6, 10) . ' hours');
            $attendances[] = Attendance::factory()->create([
                'user_id'       => $user->id,
                'date'          => $date,
                'start_time'    => $start,
                'end_time'      => $end,
            ]);
        }

        $response = $this->get("/admin/attendance/staff/{$user->id}?date={$lastMonth->format('Y-m-d')}");

        $response->assertStatus(200);
        $response->assertSee($lastMonth->format('Y/m'));

        foreach ($attendances as $attendance) {
            $response->assertSee(Carbon::parse($attendance->date)->isoFormat('MM/DD(dd)'));
            $response->assertSee(Carbon::parse($attendance->start_time)->format('H:i'));
            $response->assertSee(Carbon::parse($attendance->end_time)->format('H:i'));
            $response->assertSee($attendance->total_rest_time);
            $response->assertSee($attendance->total_work_time);
        }
    }

    /**
     * 表示月より翌月ボタンを押下したとき
     * 翌月の勤怠情報が表示されること
     */
    public function test_admin_can_view_next_month_attendances()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $nextMonth = now()->addMonth()->startOfMonth();
        $attendances = [];
        for ($i = 0; $i < 3; $i++) {
            $date = $nextMonth->copy()->setDay(rand(1, now()->daysInMonth));
            $start = (clone $date)->setTime(rand(8, 10), rand(0, 59));
            $end = (clone $start)->modify('+' . rand(6, 10) . ' hours');
            $attendances[] = Attendance::factory()->create([
                'user_id'       => $user->id,
                'date'          => $date,
                'start_time'    => $start,
                'end_time'      => $end,
            ]);
        }

        $response = $this->get("/admin/attendance/staff/{$user->id}?date={$nextMonth->format('Y-m-d')}");

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));

        foreach ($attendances as $attendance) {
            $response->assertSee(Carbon::parse($attendance->date)->isoFormat('MM/DD(dd)'));
            $response->assertSee(Carbon::parse($attendance->start_time)->format('H:i'));
            $response->assertSee(Carbon::parse($attendance->end_time)->format('H:i'));
            $response->assertSee($attendance->total_rest_time);
            $response->assertSee($attendance->total_work_time);
        }
    }

    /**
     * 勤怠一覧の「詳細」を押下したとき
     * 該当日の勤怠詳細画面に遷移できること
     */
    public function test_admin_can_view_attendance_detail_page()
    {
        $this->actingAsAdmin();

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($attendance->date->format('Y年'));
        $response->assertSee($attendance->date->isoFormat('M月D日'));
        $response->assertSee($attendance->start_time->format('H:i'));
        $response->assertSee($attendance->end_time->format('H:i'));
        foreach ($attendance->rests as $rest) {
            $response->assertSee($rest->start_time->format('H:i'));
            $response->assertSee($rest->end_time->format('H:i'));
        }
    }


    /**------------------------------------
     * 勤怠情報修正機能（管理者）
     * ------------------------------------*/
    /**
     * 承認待ちの修正申請が全て表示されている
     */
    public function test_pending_requests_are_listed()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        // 承認待ち
        $pending = AttendanceRequest::factory()->count(3)->create([
            'state' => 1,
            'user_id' => $user->id,
        ]);

        // 承認済み
        $approved = AttendanceRequest::factory()->count(2)->create([
            'state' => 2,
            'user_id' => $user->id,
        ]);

        $response = $this->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);

        // HTML に承認待ち分が含まれていること
        foreach ($pending as $req) {
            $response->assertSee((string)$req->id);
        }
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    public function test_approved_requests_are_listed()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        // 承認待ち
        $pending = AttendanceRequest::factory()->count(3)->create([
            'state' => 1,
            'user_id' => $user->id,
        ]);

        // 承認済み
        $approved = AttendanceRequest::factory()->count(3)->create([
            'state' => 2,
            'user_id' => $user->id,
        ]);

        $response = $this->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);

        foreach ($approved as $req) {
            $response->assertSee($req->user->name);
        }
    }

    /**
     * 修正申請の詳細が正しく表示される
     */
    public function test_request_detail_is_displayed()
    {
        $this->actingAsAdmin();
        $attendance = Attendance::factory()->create();

        $request = AttendanceRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'state' => 1,
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'notes' => '遅刻のため修正します',
        ]);

        $response = $this->get(route('admin.attendance.detail', ['request_id' => $request->id, 'id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSee($request->notes);
        $response->assertSee($request->start_time->format('Y年'));
        $response->assertSee($request->start_time->isoFormat('M月D日'));
        $response->assertSee($request->start_time->format('H:i'));
        $response->assertSee($request->end_time->format('H:i'));
        $response->assertSee($request->notes);
    }

    /**
     * 修正申請の承認処理が正しく動作する
     */
    public function test_request_can_be_approved()
    {
        $this->actingAsAdmin();

        $attendance = Attendance::factory()->create([
            'start_time' => now()->setTime(9, 0),
            'end_time' => now()->setTime(18, 0),
        ]);

        $request = AttendanceRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'state' => 1,
            'start_time' => now()->setTime(10, 0),
            'end_time' => now()->setTime(19, 0),
        ]);

        $this->get(route('admin.attendance.detail', ['request_id' => $request->id, 'id' => $attendance->id]));
        // 承認処理を実行
        $response = $this->followingRedirects()->post("/admin/attendance/approval", ['request_id' => $request->id]);

        // 修正申請の状態が更新されている
        $this->assertEquals(2, $request->fresh()->state);

        // 元の勤怠データが更新されている
        $this->assertEquals('10:00', $attendance->fresh()->start_time->format('H:i'));
        $this->assertEquals('19:00', $attendance->fresh()->end_time->format('H:i'));
    }

    /**------------------------------------
     * メール認証機能
     * ------------------------------------*/
    /**
     * Fortify登録後、MailHog経由で認証メールが送信される
     */
    public function test_verification_email_is_sent_after_registration()
    {
        // MailHogの代わりにNotification::fake()で検証
        Notification::fake();

        // Fortify登録API
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertStatus(302);

        // Fortify登録時はメール認証画面へリダイレクト
        $response->assertRedirect('/email/verify');

        // 登録ユーザー取得
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // 認証メール送信されたか確認
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証再送信が行われる
     */
    public function test_user_can_resend_verification_email_from_notice_page()
    {
        Notification::fake();

        // 認証前ユーザーを作成
        $user = User::factory()->unverified()->create();

        // Fortifyの「再送信ボタン」POST
        $response = $this->actingAs($user)->post('/email/verification-notification')
            ->assertStatus(302);

        // Fortifyの仕様上、再送信後は /email/verify にリダイレクト
        $response->assertRedirect('/email/verify');

        // 認証メール再送信されたか確認
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 認証リンクを開くとプロフィール設定画面に遷移する（Fortify + MailHog）
     */
    public function test_user_is_redirected_to_profile_after_email_verification()
    {
        Event::fake();

        // 認証されていないユーザー作成
        $user = User::factory()->unverified()->create();

        // Fortifyが生成する署名付きURLを模倣
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 認証リンクアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        // イベントが発火していることを確認
        Event::assertDispatched(Verified::class);

        // DBで認証済みに更新されていることを確認
        $this->assertNotNull($user->fresh()->email_verified_at);

        // 認証完了後に勤怠登録画面に遷移している
        $response->assertRedirect('/attendance');
    }
}
