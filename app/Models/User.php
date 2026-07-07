<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'login',
        'pass',
        'role',
    ];

    protected $hidden = [
        'pass',
    ];

    public function setPassAttribute($value)
    {
        $this->attributes['pass'] = md5($value);
    }
}
