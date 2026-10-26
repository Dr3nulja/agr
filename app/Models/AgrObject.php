<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgrObject extends Model
{
    // Prem — та же фирма: в списках показываем как AGR, а в отчётах ставим свой логотип
    public const COMPANIES = [1 => 'AGR', 2 => 'Prem'];
    public const COMPANY_GROUP = [1, 2];

    public const MANAGERS = [1 => 'IRA', 2 => 'KAT', 3 => 'REG', 4 => 'NAT'];

    // Состояние сдачи данных: ничего / вода сдана, тепло ещё нет / сдано всё (selDate)
    public const CHECK_NONE = 'none';
    public const CHECK_WATER = 'water';
    public const CHECK_ALL = 'all';

    protected $table = 'objects';

    // В таблице старой системы нет created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'address',
        'City',
        'GSMNR',
        'GSMNR2',
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
        'report_emails',
    ];

    protected $casts = [
        'lastSession' => 'datetime',
        'selDate' => 'datetime',
        'water_checked_at' => 'datetime',
        'heat_checked_at' => 'datetime',
        'saveHval' => 'boolean',
        'dataToPage' => 'boolean',
    ];

    public function installData(): HasMany
    {
        return $this->hasMany(ObjectInstallData::class, 'oid');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ObjectTask::class, 'object_id');
    }

    public function mobileReports(): HasMany
    {
        return $this->hasMany(MobileReport::class, 'object_id');
    }

    public function simCards(): HasMany
    {
        return $this->hasMany(SimCard::class, 'object_id')->orderBy('slot');
    }

    public function csqLogs(): HasMany
    {
        return $this->hasMany(Csq::class, 'object');
    }

    /**
     * Название компании для списков и страниц: AGR и Prem — одно и то же
     */
    public static function companyDisplayName($company): string
    {
        if (in_array((int) $company, self::COMPANY_GROUP, true)) {
            return self::COMPANIES[1];
        }

        return $company ? 'Installer #' . $company : '—';
    }

    public static function managerName($manager): string
    {
        return self::MANAGERS[(int) $manager] ?? '—';
    }

    /**
     * Логотип для отчётов — по настоящей компании объекта (public/img/logo-agr.png, logo-prem.png)
     */
    public function reportLogoPath(): ?string
    {
        $name = (int) $this->Company === 2 ? 'prem' : 'agr';
        $path = public_path('img/logo-' . $name . '.png');

        return is_file($path) ? $path : null;
    }

    public function checkState(): string
    {
        if ($this->selDate) {
            return self::CHECK_ALL;
        }

        return $this->water_checked_at ? self::CHECK_WATER : self::CHECK_NONE;
    }

    /**
     * Адреса для отчётов: через запятую (точка с запятой и пробелы тоже годятся)
     */
    public static function parseEmails(?string $emails): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $emails))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique(fn ($email) => mb_strtolower($email))
            ->values()
            ->all();
    }

    public function reportEmails(): array
    {
        return self::parseEmails($this->report_emails);
    }
}
