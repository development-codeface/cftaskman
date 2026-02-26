<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class UserLink extends Model
{
    protected $table = 'user_links';

    protected $fillable = [
        
        'title',
        
    ];
}