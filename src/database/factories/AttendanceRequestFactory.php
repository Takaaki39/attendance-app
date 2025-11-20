<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;

    public function definition()
    {
        // 開始・終了時刻（終了は開始より後に）
        $start = $this->faker->dateTimeBetween('-1 week', 'now');
        $end   = (clone $start)->modify('+' . rand(1, 8) . ' hours');

        return [
            'attendance_id' => Attendance::factory()->create()->id ?? null,
            'user_id'       => User::factory(),
            'start_time'    => $start,
            'end_time'      => $end,
            'state'         => $this->faker->randomElement([1, 2, 3]), // 1=申請中,2=承認,3=却下
            'notes'         => $this->faker->realText(50),
            'request_date'  => now(),
        ];
    }

    /**
     * attendance_id を null にしたい場合の state
     */
    public function withoutAttendance()
    {
        return $this->state(fn() => [
            'attendance_id' => null,
        ]);
    }
}
