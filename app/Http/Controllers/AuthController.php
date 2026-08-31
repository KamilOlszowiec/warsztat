<?php

// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Models\Uzytkownik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    public function showRegister() { return view('auth.register'); }

    public function register(Request $request)
    {
        $data = $request->validate([
            'imie' => ['required', 'string', 'max:50'],
            'nazwisko' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:uzytkownicy,email'],
            'telefon' => ['nullable', 'string', 'max:15'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $uzytkownik = Uzytkownik::create([
            'imie' => $data['imie'],
            'nazwisko' => $data['nazwisko'],
            'email' => $data['email'],
            'telefon' => $data['telefon'] ?? null,
            'password' => Hash::make($data['password']),
            'rola' => 'klient',
        ]);

        event(new Registered($uzytkownik)); // to wysyła maila weryfikacyjnego

        Auth::login($uzytkownik);
        return redirect()->route('verification.notice');
    }

    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Nieprawidłowe dane logowania.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}