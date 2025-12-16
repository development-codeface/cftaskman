<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLogs extends Model
{
    //
    protected $table = 'work_logs';
    protected $fillable = ['user_id','task_id','hours'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
