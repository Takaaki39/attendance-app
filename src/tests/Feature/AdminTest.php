<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

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

    /* --------------------------------------------------------------
        勤怠一覧情報取得機能（管理者）
    -------------------------------------------------------------- */

    /**
     * 管理者はその日の全ユーザーの勤怠情報を確認できる
     */
    public function testAdminCanSeeAllAttendanceOfDay()
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
    public function testAdminAttendanceListShowsTodayDate()
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
    public function testAdminCanViewPreviousDayAttendance()
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
    public function testAdminCanViewNextDayAttendance()
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
    public function testAdminCanViewCorrectAttendanceDetail()
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
    public function testAdminStartTimeValidationError()
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
    public function testAdminRestStartTimeValidationError()
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
    public function testAdminRestEndTimeValidationError()
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
    public function testAdminNoteIsRequired()
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
    public function testAdminCanViewAllUsers()
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
    public function testAdminCanViewSpecificUserAttendances()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $attendances = [];
        $usedDays = [];
        for ($i = 0; $i < 3; $i++) {
            do {
                $day = rand(1, now()->daysInMonth);
            } while (in_array($day, $usedDays));
            $usedDays[] = $day;
            $date = now()->startOfMonth()->setDay($day);
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
    public function testAdminCanViewLastMonthAttendances()
    {
        $this->actingAsAdmin();

        $user = User::factory()->create();

        $lastMonth = now()->subMonth()->startOfMonth();

        $attendances = [];
        $usedDays = [];
        for ($i = 0; $i < 3; $i++) {
            do {
                $day = rand(1, $lastMonth->copy()->daysInMonth);
            } while (in_array($day, $usedDays));
            $usedDays[] = $day;
            $date = $lastMonth->copy()->setDay($day);
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
    public function testAdminCanViewNextMonthAttendances()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $nextMonth = now()->addMonth()->startOfMonth();
        $attendances = [];
        $usedDays = [];
        for ($i = 0; $i < 3; $i++) {
            do {
                $day = rand(1, $nextMonth->copy()->daysInMonth);
            } while (in_array($day, $usedDays));
            $usedDays[] = $day;
            $date = $nextMonth->copy()->setDay($day);
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
    public function testAdminCanViewAttendanceDetailPage()
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
    public function testPendingRequestsAreListed()
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

        $response = $this->get('/admin/stamp-correction-request/list');
        $response->assertStatus(200);

        // HTML に承認待ち分が含まれていること
        foreach ($pending as $req) {
            $response->assertSee((string)$req->id);
        }
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    public function testApprovedRequestsAreListed()
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

        $response = $this->get('/admin/stamp-correction-request/list');

        $response->assertStatus(200);

        foreach ($approved as $req) {
            $response->assertSee($req->user->name);
        }
    }

    /**
     * 修正申請の詳細が正しく表示される
     */
    public function testRequestDetailIsDisplayed()
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
    public function testRequestCanBeApproved()
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
}
