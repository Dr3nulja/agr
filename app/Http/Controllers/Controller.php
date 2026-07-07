<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    protected function logAction(string $content): void
    {
        $user = session('user');

        if ($user && isset($user->login)) {
            $content = sprintf('[%s] %s', $user->login, $content);
        }

        Log::create(['Content' => $content]);
    }
}
