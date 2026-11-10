<?php
namespace App\Http\Controllers;

use App\Models\Uzytkownik;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private const MAKS_PROB = 5;
    private const BLOKADA_SEKUND = 60;

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

        event(new Registered($uzytkownik)); // mail weryfikujący

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

        // Ochrona przed zgadywaniem hasła
        $klucz = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($klucz, self::MAKS_PROB)) {
            $sekundy = RateLimiter::availableIn($klucz);

            return back()
                ->withErrors(['email' => "Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za {$sekundy} s."])
                ->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($klucz);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($klucz, self::BLOKADA_SEKUND);

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