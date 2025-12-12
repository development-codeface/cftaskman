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
        'end_date'
    ];

    public function categoryName()
    {
        return $this->belongsTo(Categories::class, 'category_id', 'id')->select('id', 'name');
    }
}
