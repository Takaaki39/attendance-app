<?php

namespace Database\Factories;

use App\Models\AttendanceRest;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRestFactory extends Factory
{
    protected $model = AttendanceRest::class;

    public function definition()
    {
        $start = $this->faker->dateTimeBetween('-1 hour', 'now');
        $end = (clone $start)->modify('+' . rand(10, 60) . ' minutes');

        return [
            'attendance_id' => Attendance::factory(),
            'start_time' => $start,
            'end_time' => $end,
        ];
    }
}
