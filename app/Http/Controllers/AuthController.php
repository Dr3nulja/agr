<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Форма входа
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Обработка входа
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('login', $validated['login'])
                   ->where('pass', md5($validated['password']))
                   ->first();

        if (!$user) {
            return back()->withErrors(['login' => 'Неверные учетные данные'])->onlyInput('login');
        }

        // Сохраняем данные в сессию
        session(['user_id' => $user->id, 'user' => $user]);
        $this->logAction('Logged in');

        return redirect()->route('dashboard');
    }

    /**
     * Выход
     */
    public function logout()
    {
        $user = session('user');

        session()->forget(['user_id', 'user']);

        if ($user && isset($user->login)) {
            $this->logAction('Logged out');
        }

        return redirect()->route('login');
    }

    /**
     * Профиль пользователя
     */
    public function profile()
    {
        $user = session('user');
        return view('auth.profile', ['user' => $user]);
    }
}
