<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

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
        $this->attributes['pass'] = Hash::make($value);
    }

    /**
     * Legacy accounts store an MD5 digest instead of a bcrypt hash.
     * Verify against either format without breaking existing logins.
     */
    public function isLegacyMd5Hash(): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/i', $this->attributes['pass']);
    }

    public function verifyPassword(string $plain): bool
    {
        if ($this->isLegacyMd5Hash()) {
            return hash_equals($this->attributes['pass'], md5($plain));
        }

        return Hash::check($plain, $this->attributes['pass']);
    }
}
