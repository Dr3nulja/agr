<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectInstallData extends Model
{
    protected $table = 'objects_install_data';

    protected $fillable = [
        'oid',
        'devid',
        'location',
        'devtype',
        'dnType',
        'len',
        'foto1',
        'foto2',
        'place1',
        'place2',
        'comment',
    ];

    public function object(): BelongsTo
    {
        return $this->belongsTo(AgrObject::class, 'oid');
    }
}
