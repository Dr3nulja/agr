# Развёртывание на сервере

Архив уже содержит `vendor/` (production-зависимости), composer на сервере не нужен.
Проверено на копии боевой базы `agrBase` (дамп от 07.07.2026).

## Требования

- PHP 8.2+ с расширениями: pdo_mysql, mbstring, xml, zip, gd, fileinfo, openssl, curl
- MySQL 8 (как на старом сервере) или MariaDB
- nginx или Apache, корень сайта — папка `public/`
- HTTPS (release-версия приложения QRScannerApp ходит только по https)

## 1. База

Залить дамп старой базы:

```bash
mysql -e "CREATE DATABASE agrBase CHARACTER SET utf8mb4"
mysql agrBase < dump.sql
```

Хранимые процедуры старой системы (`NewObject`, `NewElekterObject`, `CheckObject`)
не нужны: в дампе их нет, и таблицы нового объекта (`object_N`, `lastdata_N`, `mlog_N`,
`flat_N`, `heat_N` + триггер `ins_data_N`) сайт создаёт сам — по схеме последних
объектов старой базы. Если процедуры в базе всё же есть, сайт вызывает их.

## 2. Файлы

```bash
mkdir -p /var/www/agr && cd /var/www/agr
tar -xzf agr.tar.gz        # или unzip agr.zip
cp .env.example .env
nano .env                  # APP_URL, DB_DATABASE / DB_USERNAME / DB_PASSWORD
php artisan key:generate
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
```

Что делает `migrate` на старой базе (всё проверено на копии):

- базовые таблицы (`users`, `objects`, `log`, ...) не трогает — они уже есть;
- создаёт `object_tasks`, `mobile_reports`, `sim_cards`;
- переносит SIM из `objects` в регистр `sim_cards`. Если один номер/ICCID записан
  на нескольких объектах (в дампе таких 13), карта достаётся активному объекту,
  который позже всех выходил на связь. Такие карты видны в регистре под
  «Kontrollida» — проверить можно когда угодно, развёртыванию это не мешает;
- расширяет `objects.pin1/pin2` до 8 символов и `users.pass` до 255
  (при входе пароль переводится с MD5 на bcrypt). Только расширение, данные не меняются.
- добавляет в `objects` колонки `water_checked_at`, `heat_checked_at` (два чека: вода / тепло)
  и `report_emails` (куда слать отчёты); создаёт `reading_corrections` (журнал ручных правок
  показаний) и `monthly_readings` (зафиксированные месячные показания).

Почта для кнопки «Saada»: `MAIL_*` в `.env` (SMTP). Пока `MAIL_MAILER=log`, письма не уходят,
а пишутся в `storage/logs/laravel.log`.

Логотипы в отчётах: `public/img/logo-agr.png` и `public/img/logo-prem.png` (по колонке `Company`).
Если файла нет — отчёт строится без логотипа.

Пользователи и пароли — из старой базы, вход тем же логином/паролем.

## 3. nginx

```nginx
server {
    listen 80;
    server_name <домен>;
    root /var/www/agr/public;
    index index.php;
    client_max_body_size 20M;   # отчёты из приложения с фото

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

В `php.ini` (fpm): `post_max_size = 20M`, `upload_max_filesize = 20M`.
HTTPS: `certbot --nginx -d <домен>`.

## 4. Важно

- Модемы на объектах ходят на `/api/command`, `/api/ingest-data`, `/api/lora-ingest`,
  `/api/device-id(s)`. Пока домен смотрит на старый сервер, данные идут туда.
- Фото и подписи из приложения лежат в `storage/app/mobile_reports` —
  бэкапить вместе с базой.
- Триггер `ins_data_N` у нового объекта создаётся от пользователя базы из `.env`.
  Если на MySQL 8 включён binlog, а у пользователя нет SUPER, нужно один раз
  `SET GLOBAL log_bin_trust_function_creators = 1;` (или в `my.cnf`). Без этого объект
  всё равно создаётся, но в логе будет предупреждение «Trigger ins_data_N was not created».
- Ошибки: `storage/logs/laravel.log`, `/var/log/nginx/error.log`.
