<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSession
{
    /**
     * Проверка наличия пользователя в сессии
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('user_id') || !session('user')) {
            return redirect()->route('login')->with('error', 'Palun logi sisse');
        }

        return $next($request);
    }
}
