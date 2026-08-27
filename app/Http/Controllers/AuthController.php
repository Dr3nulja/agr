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

        $user = User::where('login', $validated['login'])->first();

        if (!$user || !$user->verifyPassword($validated['password'])) {
            return back()->withErrors(['login' => 'Неверные учетные данные'])->onlyInput('login');
        }

        // Прозрачно переводим старые MD5-хэши на bcrypt при успешном входе
        if ($user->isLegacyMd5Hash()) {
            $user->pass = $validated['password'];
            $user->save();
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
