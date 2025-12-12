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
        'deadline',
        'status',
        'created_by',
        'assigned_to'
    ];
}
