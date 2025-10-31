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
        'notes',
        'start_time',
        'end_time',
    ];

    /**
     * 型キャスト
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * ユーザー（多対1）リレーション
     * 出勤データは1人のユーザーに属する
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 出勤申請（1対多）リレーション
     * 出勤データに複数の申請が紐づく
     */
    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }
}
