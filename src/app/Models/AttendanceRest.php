<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRest extends Model
{
    use HasFactory;

    protected $table = 'attendance_rests';

    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * 出勤データ（多対1）
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
