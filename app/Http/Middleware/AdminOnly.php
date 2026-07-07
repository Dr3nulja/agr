<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session('user');

        if (! $user || ! isset($user->role) || (int) $user->role !== 1) {
            return redirect()->route('objects.index')->with('error', 'У вас нет прав для доступа к этой странице.');
        }

        return $next($request);
    }
}
