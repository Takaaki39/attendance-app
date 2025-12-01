<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        // ランダムな日付（今日まで）
        $date = $this->faker->dateTimeBetween('-1 month', 'now');
        $start = (clone $date)->setTime(rand(8, 10), rand(0, 59));
        $end = (clone $start)->modify('+' . rand(6, 10) . ' hours');

        return [
            'user_id' => User::factory(),
            'date' => $date->format('Y-m-d'),

            // 任意
            'notes' => $this->faker->optional()->sentence(),

            // 基本は正しい勤務データ
            'start_time' => $start,
            'end_time'   => $end,
        ];
    }
}
