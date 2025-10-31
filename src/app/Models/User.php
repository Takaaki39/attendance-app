<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    /**
     * 出勤情報（1対多）
     * ユーザーは複数の出勤データを持つ
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * 最新の出勤データ
     */
    public function latestAttendance()
    {
        return $this->hasOne(Attendance::class)->latestOfMany();
    }

    /**
     * 指定日の出勤データ
     */
    public function attendanceOnDate($date)
    {
        return $this->attendances()
            ->whereDate('start_time', $date)
            ->first();
    }
}
