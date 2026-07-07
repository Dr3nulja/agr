<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = \Illuminate\Http\Request::capture());

use App\Models\AgrObject;

$objects = AgrObject::all();
foreach ($objects as $obj) {
    echo "ID: {$obj->id}, Address: {$obj->address}, Status: " . var_export($obj->status, true) . "\n";
}
