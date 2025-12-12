<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $table = 'notifications'; // change if your table name differs

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // If timestamps exist (created_at, updated_at), keep this as true
    public $timestamps = true;

    /**
     * Relationship: Notification belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
