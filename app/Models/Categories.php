<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;


class Categories extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name'
    ];
}
