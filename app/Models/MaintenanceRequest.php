<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Hooldus: заявка на обслуживание из мобильного приложения (вода или тепло + адрес)
 */
class MaintenanceRequest extends Model
{
    public const KINDS = [
        'water' => 'Vesi',
        'heat' => 'Soojus',
    ];

    protected $table = 'maintenance_requests';

    protected $fillable = [
        'kind',
        'address',
        'comment',
        'installer',
        'client_uuid',
        'done_at',
    ];

    protected $casts = [
        'done_at' => 'datetime',
    ];

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
