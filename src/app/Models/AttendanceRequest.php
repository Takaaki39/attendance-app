<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $table = 'attendance_requests';

    protected $fillable = [
        'attendance_id',
        'state',
        'notes',
        'request_date',
    ];

    protected $casts = [
        'request_date' => 'datetime',
    ];

    /**
     * 出勤データ（多対1）
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
