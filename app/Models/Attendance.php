<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'clockin_description',
        'clockout_description',
        'attendance_date',
        'auto_checkout',
        'is_absent',
        'work_minutes',
         'link_ids'
    ];

    protected $casts = [
    'link_ids' => 'array'
];
}
