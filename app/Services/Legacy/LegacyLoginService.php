<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class LegacyLoginService
{
    public function attempt(string $login, string $password): ?array
    {
        $user = DB::table('users')
            ->select(['id', 'name', 'login', 'role'])
            ->where('login', $login)
            ->whereRaw('pass = MD5(?)', [$password])
            ->first();

        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'login' => $user->login,
            'role' => $user->role,
        ];
    }
}