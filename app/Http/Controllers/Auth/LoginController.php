<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Legacy\LegacyLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('oid')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request, LegacyLoginService $legacyLoginService): RedirectResponse
    {
        $credentials = $request->validate([
            'uname' => ['required', 'string'],
            'pname' => ['required', 'string'],
        ]);

        $user = $legacyLoginService->attempt($credentials['uname'], $credentials['pname']);

        if ($user === null) {
            return back()
                ->withInput($request->only('uname'))
                ->withErrors(['uname' => 'Invalid login or password.']);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'oid' => $user['login'],
            'name' => $user['name'],
            'role' => $user['role'],
        ]);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->flush();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }
}