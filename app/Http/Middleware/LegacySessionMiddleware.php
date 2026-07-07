<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacySessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('oid')) {
            return redirect()->route('login.form');
        }

        return $next($request);
    }
}