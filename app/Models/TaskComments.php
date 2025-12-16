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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
