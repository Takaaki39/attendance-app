<?php

namespace App\Models;

use Carbon\Carbon;
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

    protected $appends = [
        'status',
        'status_label',
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

    /**
     * ステータス判定アクセサ
     * - 勤務外
     * - 勤務中
     * - 休憩中
     * - 退勤済
     */
    public function getStatusAttribute()
    {
        // まず今日の出勤かどうか判定
        if (!$this->start_time || !Carbon::parse($this->start_time)->isToday()) {
            return 0;
        }

        // 退勤済み
        if ($this->end_time) {
            return 3;
        }

        // 休憩中かどうか
        $ongoingRest = $this->rests()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($ongoingRest) {
            return 2;
        }

        // 上記以外 → 勤務中
        return 1;
    }

    /**
     * ステータスラベル（status と同じ）
     */
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 0:
                return '勤務外';
            case 1:
                return '勤務中';
            case 2:
                return '休憩中';
            case 3:
                return '退勤済';
        }
    }
}
