<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRestRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'rest_id',      // null OK
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    /**
     * 申請データ（多対1）
     */
    public function requests()
    {
        return $this->belongsTo(AttendanceRequest::class, 'request_id');
    }
}
