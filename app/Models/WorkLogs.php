<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLogs extends Model
{
    //
    protected $table = 'work_logs';
    protected $fillable = ['user_id','project_id','task_id','work_date','hours','description'];
}
