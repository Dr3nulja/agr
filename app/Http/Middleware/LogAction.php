<?php

namespace App\Http\Middleware;

use App\Models\Log;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('get')) {
            return $response;
        }

        $user = session('user');
        if (!$user || !isset($user->login)) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route?->getName();
        $path = $request->getPathInfo();

        $content = sprintf(
            '[%s] Viewed %s %s%s',
            $user->login,
            $request->method(),
            $path,
            $routeName ? ' (' . $routeName . ')' : ''
        );

        Log::create(['Content' => $content]);

        return $response;
    }
}
