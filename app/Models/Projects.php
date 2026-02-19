<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Projects extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'description',
        'status',
        'created_by',
        'start_date',
        'end_date',
        'task_count'
    ];

    public function categoryName()
    {
        return $this->belongsTo(Categories::class, 'category_id', 'id')->select('id', 'name');
    }

    public function assignments()
    {
        return $this->hasMany(ProjectAssignments::class, 'project_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'project_assignments', 'project_id', 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Tasks::class, 'project_id');
    }
}
