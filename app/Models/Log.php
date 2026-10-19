<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'log';

    // Схема старой системы: дата в ins_date (DEFAULT CURRENT_TIMESTAMP), updated_at нет
    public const CREATED_AT = 'ins_date';
    public const UPDATED_AT = null;

    protected $fillable = [
        'Content',
    ];

    // В боевой базе Content — varchar(255) NOT NULL
    public function setContentAttribute($value): void
    {
        $this->attributes['Content'] = mb_substr((string) $value, 0, 255);
    }
}
