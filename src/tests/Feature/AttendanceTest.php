<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
