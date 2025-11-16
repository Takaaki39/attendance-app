<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * 対応するテーブル名
     */
    protected $table = 'attendances';

    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'user_id',
        'date',
        'notes',
        'start_time',
        'end_time',
    ];

    /**
     * 型キャスト
     */
    protected $casts = [
        'date'          => 'date:Y-m-d',
        'start_time'    => 'datetime',
        'end_time'      => 'datetime',
    ];

    /**
     * ユーザー（多対1）リレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩（1対多）リレーション
     */
    public function rests()
    {
        return $this->hasMany(AttendanceRest::class);
    }

    /**
     * 出勤申請（1対多）リレーション
     */
    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    /**
     *  休憩時間の合計（分単位 → H:i 表示）
     */
    public function getTotalRestTimeAttribute()
    {
        $totalMinutes = 0;

        foreach ($this->rests as $rest) {
            if ($rest->start_time && $rest->end_time) {
                $totalMinutes += $rest->end_time->diffInMinutes($rest->start_time);
            }
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     *  勤務時間の合計（出勤〜退勤 − 休憩）
     */
    public function getTotalWorkTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return '';
        }

        $workMinutes = $this->end_time->diffInMinutes($this->start_time);

        // 休憩分を引く
        foreach ($this->rests as $rest) {
            if ($rest->start_time && $rest->end_time) {
                $workMinutes -= $rest->end_time->diffInMinutes($rest->start_time);
            }
        }

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
