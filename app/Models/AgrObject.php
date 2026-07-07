<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgrObject extends Model
{
    protected $table = 'objects';

    protected $fillable = [
        'address',
        'City',
        'GSMNR',
        'IMEI',
        'IMEI2',
        'Contact',
        'Description',
        'Description2',
        'Company',
        'dtype',
        'status',
        'Devqtty',
        'RadioDevQty',
        'MainRadio',
        'GSMSERIAL',
        'GSMSERIAL2',
        'pin1',
        'pin2',
        'puk1',
        'puk2',
        'KeyCode',
        'manager',
        'packet',
        'traffic',
        'callCnt',
        'summ',
        'ver',
        'lastSession',
        'selDate',
        'lat',
        'lon',
        'saveHval',
        'm2_andur',
        'dataToPage',
        'AddFee',
        'fee',
        'kuluM2',
        'AlgLopp',
    ];

    protected $casts = [
        'lastSession' => 'datetime',
        'selDate' => 'datetime',
        'saveHval' => 'boolean',
        'dataToPage' => 'boolean',
    ];

    public function installData(): HasMany
    {
        return $this->hasMany(ObjectInstallData::class, 'oid');
    }

    public function csqLogs(): HasMany
    {
        return $this->hasMany(Csq::class, 'object');
    }
}
