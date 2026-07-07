<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo Schema::hasTable('object_5') ? 'object_yes' : 'object_no';
echo '|';
echo Schema::hasTable('lastdata_5') ? 'last_yes' : 'last_no';
