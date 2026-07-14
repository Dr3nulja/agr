<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AgrObject;
use App\Models\ObjectInstallData;
use App\Models\Log;
use App\Models\Csq;
use Illuminate\Http\Request;

/**
 * Примеры использования моделей для AGR системы
 * 
 * Все примеры ниже демонстрируют базовые операции CRUD
 */

class ExampleController extends Controller
{
    /**
     * ==================== USERS ====================
     */
    
    public function userExamples()
    {
        // Создание пользователя
        $user = User::create([
            'name' => 'John Doe',
            'login' => 'johndoe',
            'pass' => 'password123', // Автоматически хешируется в MD5
            'role' => 1, // 1 = admin, 0 = user
        ]);

        // Получить пользователя по login
        $user = User::where('login', 'johndoe')->first();

        // Получить все администраторов
        $admins = User::where('role', 1)->get();

        // Обновить пользователя
        $user->update(['name' => 'Jane Doe']);

        // Удалить пользователя
        $user->delete();
    }

    /**
     * ==================== OBJECTS ====================
     */
    
    public function objectExamples()
    {
        // Создание нового объекта
        $object = AgrObject::create([
            'address' => 'Main Street 123',
            'City' => 'Tallinn',
            'GSMNR' => '+37259123456',
            'IMEI' => '862049044965471',
            'Contact' => 'John Doe',
            'Description' => 'Residential building',
            'Company' => 1,
            'dtype' => 1, // 1=Water meter, 2=Heat meter, 3=Electric meter
            'status' => 1,
            'Devqtty' => 5,
            'manager' => 1,
            'lat' => 59.4370,
            'lon' => 24.7536,
        ]);

        // Получить все активные объекты компании
        $objects = ObjectModel::where('Company', 1)
                             ->where('status', 1)
                             ->orderBy('address')
                             ->get();

        // Получить объект по IMEI
        $object = AgrObject::where('IMEI', '862049044965471')->first();

        // Получить объект с его данными установки
        $object = ObjectModel::with('installData')->find(1);

        // Получить все ошибки объекта
        $object = ObjectModel::find(1);
        $errors = $object->csqLogs()->where('csq', '<', 5)->get();

        // Обновить объект
        $object->update([
            'lastSession' => now(),
            'ver' => 2,
        ]);

        // Удалить объект (каскадно удалятся связанные данные)
        $object->delete();
    }

    /**
     * ==================== OBJECTS INSTALL DATA ====================
     */
    
    public function installDataExamples()
    {
        // Добавить данные установки
        $installData = ObjectInstallData::create([
            'oid' => 1,
            'devid' => 1001,
            'location' => 'Floor 1, Apartment 1',
            'devtype' => 1,
            'len' => 50,
            'place1' => 'Kitchen',
            'place2' => 'Bathroom',
            'comment' => 'Installed on 2024-01-15',
        ]);

        // Получить все устройства объекта
        $devices = ObjectInstallData::where('oid', 1)->get();

        // Получить устройства конкретного типа
        $waterMeters = ObjectInstallData::where('oid', 1)
                                       ->where('devtype', 1)
                                       ->get();

        // Получить объект через installData
        $object = ObjectInstallData::find(1)->object;

        // Обновить данные
        $installData->update(['comment' => 'Updated comment']);

        // Удалить
        $installData->delete();
    }

    /**
     * ==================== LOGGING ====================
     */
    
    public function logExamples()
    {
        // Добавить лог
        Log::create([
            'Content' => 'User admin logged in',
        ]);

        // Получить все логи за последний день
        $recentLogs = Log::where('ins_date', '>=', now()->subDay())
                ->orderBy('ins_date', 'desc')
                ->get();

        // Получить последние 100 логов
        $logs = Log::orderBy('ins_date', 'desc')
              ->limit(100)
              ->get();

        // Удалить старые логи (старше 3 месяцев)
        Log::where('ins_date', '<', now()->subMonths(3))->delete();
    }

    /**
     * ==================== CSQ (SIGNAL QUALITY) ====================
     */
    
    public function csqExamples()
    {
        // Добавить запись качества сигнала
        Csq::create([
            'object' => 1,
            'csq' => 25, // 0-31, где 31 - лучший сигнал
        ]);

        // Получить последние записи о качестве сигнала объекта
        $signalQuality = Csq::where('object', 1)
                          ->orderBy('created_at', 'desc')
                          ->limit(10)
                          ->get();

        // Получить среднее качество сигнала за день
        $avgQuality = Csq::where('object', 1)
                        ->where('created_at', '>=', now()->startOfDay())
                        ->avg('csq');

        // Найти объекты с плохим сигналом
        $poorSignal = Csq::where('csq', '<', 5)
                        ->where('created_at', '>=', now()->subHours(1))
                        ->with('object')
                        ->get();
    }

    /**
     * ==================== ОТНОШЕНИЯ (RELATIONSHIPS) ====================
     */
    
    public function relationshipExamples()
    {
        // Получить объект с всеми связанными данными
        $object = ObjectModel::with(['installData', 'csqLogs'])
                            ->find(1);

        // Получить данные установки с объектом
        $installData = ObjectInstallData::with('object')->get();

        // Lazy Loading (загрузка при необходимости)
        $object = ObjectModel::find(1);
        $installData = $object->installData; // Загружается здесь

        // Получить объекты с количеством устройств
        $objects = ObjectModel::withCount('installData')->get();
        foreach ($objects as $obj) {
            echo $obj->address . ': ' . $obj->install_data_count . ' devices';
        }
    }

    /**
     * ==================== ЗАПРОСЫ И ФИЛЬТРАЦИЯ ====================
     */
    
    public function queryExamples()
    {
        // Сложный запрос с фильтрацией
        $objects = ObjectModel::where('Company', 1)
                            ->where('status', 1)
                            ->where('dtype', '>=', 1)
                            ->where('dtype', '<=', 3)
                            ->orderBy('address')
                            ->paginate(15);

        // OR условие
        $objects = ObjectModel::where('Company', 1)
                            ->where(function ($query) {
                                $query->where('dtype', 1)
                                      ->orWhere('dtype', 2);
                            })
                            ->get();

        // IN оператор
        $objects = ObjectModel::whereIn('dtype', [1, 2, 3])
                            ->get();

        // Поиск по LIKE
        $objects = ObjectModel::where('address', 'like', '%Street%')
                            ->get();

        // Агрегация
        $count = ObjectModel::where('Company', 1)->count();
        $total = ObjectModel::where('dtype', 3)->sum('Devqtty');
        $avg = ObjectModel::avg('fee');
    }
}
