<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComments extends Model
{
    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'comment'
    ];
}
