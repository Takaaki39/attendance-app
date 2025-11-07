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
        'user_id',
        'start_time',
        'end_time',
        'state',
        'notes',
        'request_date',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'request_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 出勤データ（多対1）
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 休憩（1対多）リレーション
     */
    public function restRequests()
    {
        return $this->hasMany(AttendanceRestRequest::class, 'request_id');
    }

    public function getStateLabelAttribute()
    {
        return match ($this->state) {
            1 => '承認待ち',
            2 => '承認済み',
            3 => '却下',
            default => '不明',
        };
    }
}
