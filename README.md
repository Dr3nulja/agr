<<<<<<< HEAD
# AGR Laravel - Система управления приборами учета

Проект переделки наследственного PHP приложения на современный Laravel 11 фреймворк.

## 📋 О проекте

AGR (Автоматическая Система Учета Ресурсов) - это система для управления распределенными приборами учета (счетчиками) воды, электроэнергии и отопления в жилых и коммерческих зданиях.

**Основной функционал:**
- Управление объектами (дома, здания)
- Управление приборами учета (счетчики)
- Отслеживание показаний в реальном времени
- GSM коммуникация с удаленными устройствами
- История данных и логирование
- Многопользовательская система с ролями
- Экспорт данных в различные форматы

## 🚀 Быстрый старт

### Требования
- PHP 8.2+
- MySQL 5.7+
- Composer
- Node.js (опционально для фронтенда)

### Установка

1. **Установить зависимости PHP:**
```bash
cd c:\Users\admin\Desktop\agr-laravel
composer install
```

2. **Создать .env файл (уже создан):**
```bash
cp .env.example .env
php artisan key:generate
```

3. **База данных уже создана и готова к использованию:**
- Название: `agr`
- Все таблицы созданы через миграции
- Тестовый администратор добавлен

4. **Запустить сервер разработки:**
```bash
php artisan serve
```
Приложение будет доступно на http://localhost:8000

## 🗄️ База данных

### Структура БД

База данных `agr` содержит следующие основные таблицы:

| Таблица | Описание |
|---------|---------|
| `users` | Пользователи системы с ролями |
| `objects` | Основные объекты (дома, здания) |
| `objects_install_data` | Данные установки приборов |
| `log` | Логирование событий системы |
| `csq` | Качество сигнала GSM |

**Динамические таблицы** (создаются для каждого объекта):
- `object_{id}` - Список приборов объекта
- `lastdata_{id}` - Последние показания
- `mlog_{id}` - История показаний
- `flat_{id}` - Список квартир
- `heat_{id}` - Данные отопления

### Подробная документация

Полную документацию по структуре БД смотрите в [docs/DATABASE.md](docs/DATABASE.md)

## 👤 Тестовый администратор

```
Логин: admin
Пароль: admin123
```

## 🔧 Модели Eloquent

Созданы следующие модели для работы с БД:

```
App\Models\User
App\Models\Object
App\Models\ObjectInstallData
App\Models\Log
App\Models\Csq
```

Примеры использования смотрите в [app/Http/Controllers/ExampleController.php](app/Http/Controllers/ExampleController.php)

## 📝 Примеры использования

### Получить все объекты компании
```php
use App\Models\Object;

$objects = Object::where('Company', 1)
                 ->where('status', 1)
                 ->get();
```

### Создать новый объект
```php
$object = Object::create([
    'address' => 'Street 123',
    'City' => 'Tallinn',
    'IMEI' => '862049044965471',
    'Company' => 1,
    'dtype' => 1,
]);
```

### Получить объект с его приборами
```php
$object = Object::with('installData')->find(1);
```

## 📖 Миграции

### Создать/пересоздать БД
```bash
# Пересоздать все таблицы и добавить тестовые данные
php artisan migrate:fresh --seed

# Только миграции без seeder
php artisan migrate

# Откатить последнюю миграцию
php artisan migrate:rollback

# Откатить все миграции
php artisan migrate:reset
```

## 🧪 Тестирование

```bash
# Запустить все тесты
php artisan test

# Запустить конкретный тест
php artisan test tests/Feature/UserTest.php
```

## 🎯 План миграции

Миграция выполняется поэтапно:

1. **Фаза 1**: Структура БД (✅ ГОТОВО)
   - Модели данных
   - Миграции
   - Seeders

2. **Фаза 2**: Аутентификация
   - Система логина
   - Управление ролями
   - Сессии

3. **Фаза 3**: Управление объектами
   - CRUD операции
   - Валидация
   - API endpoints

4. **Фаза 4**: Управление приборами
   - Синхронизация данных
   - История показаний
   - Экспорт

5. **Фаза 5**: Интеграция старой системы
   - Адаптеры для совместимости
   - Миграция данных
   - Тестирование

Подробный план: [docs/migration-plan.md](docs/migration-plan.md)

## 📁 Структура проекта

```
agr-laravel/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── ExampleController.php      # Примеры использования
│   ├── Models/                            # Eloquent модели
│   │   ├── User.php
│   │   ├── Object.php
│   │   ├── ObjectInstallData.php
│   │   ├── Log.php
│   │   └── Csq.php
│   └── Services/
│       └── Legacy/                        # Адаптеры для старого кода
├── database/
│   ├── migrations/                        # Миграции
│   ├── seeders/
│   │   └── DatabaseSeeder.php            # Тестовые данные
│   ├── create_agr_database.sql           # SQL скрипт создания БД
│   └── factories/
├── docs/
│   ├── DATABASE.md                        # Документация БД
│   ├── migration-plan.md                  # План миграции
│   └── ...
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── .env                                   # Конфигурация (уже установлена)
├── composer.json                          # PHP зависимости
└── artisan                               # Laravel CLI
```

## 🔄 Синхронизация с разработкой

Рекомендуемый процесс разработки:

1. Создать feature branch: `git checkout -b feature/my-feature`
2. Внести изменения
3. Протестировать: `php artisan test`
4. Запушить и создать Pull Request

## 📚 Полезные команды

```bash
# Генерировать новую модель с миграцией и фабрикой
php artisan make:model ModelName -mf

# Генерировать контроллер
php artisan make:controller MyController

# Генерировать миграцию
php artisan make:migration create_table_name

# Генерировать seeder
php artisan make:seeder TableNameSeeder

# Просмотр всех routes
php artisan route:list

# Очистить кэш
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Запустить очереди
php artisan queue:work
```

## 🐛 Отладка

### Включить debug режим
В .env установите `APP_DEBUG=true`

### Просмотр логов
```bash
tail -f storage/logs/laravel.log
```

### Tinker - интерактивная консоль
```bash
php artisan tinker

> $user = App\Models\User::first();
> $user->name = 'New Name';
> $user->save();
```

## 🔐 Безопасность

⚠️ **ВАЖНО**: Текущая система использует MD5 для хеширования паролей (как в старой системе). 

**Рекомендуется** перейти на bcrypt:
```php
public function setPassAttribute($value)
{
    $this->attributes['pass'] = bcrypt($value);
}
```

## 📞 Поддержка

По вопросам о структуре БД или использовании моделей смотрите:
- [docs/DATABASE.md](docs/DATABASE.md) - Полная документация БД
- [app/Http/Controllers/ExampleController.php](app/Http/Controllers/ExampleController.php) - Примеры кода
- [Laravel документация](https://laravel.com/docs/11.x)

## 📄 Лицензия

Proprietary - закрытый проект
=======
# agr
>>>>>>> dc88ec8a54c963badff211339392a870eb540402
