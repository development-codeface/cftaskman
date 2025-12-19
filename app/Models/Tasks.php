<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    protected $table = 'tasks';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'assigned_to'
    ];

      public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TaskComments::class, 'task_id');
    }

    public function worklogs()
    {
        return $this->hasMany(Worklogs::class, 'task_id');
    }
}
