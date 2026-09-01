<?php

// app/Http/Controllers/Admin/UserController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Uzytkownik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = Uzytkownik::orderBy('nazwisko')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'imie' => ['required', 'string', 'max:50'],
            'nazwisko' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:uzytkownicy,email'],
            'telefon' => ['nullable', 'string', 'max:15'],
            'rola' => ['required', Rule::in(['klient', 'pracownik', 'administrator'])],
            'password' => ['required', Password::min(8)],
        ]);

        $user = Uzytkownik::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(), // konta tworzone przez admina od razu aktywne
        ]);

        return redirect()->route('admin.users.index')->with('status', "Konto {$user->imie} {$user->nazwisko} zostało utworzone.");
    }

    public function edit(Uzytkownik $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, Uzytkownik $user)
    {
        $data = $request->validate([
            'imie' => ['required', 'string', 'max:50'],
            'nazwisko' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('uzytkownicy', 'email')->ignore($user->id_uzytkownika, 'id_uzytkownika')],
            'telefon' => ['nullable', 'string', 'max:15'],
            'rola' => ['required', Rule::in(['klient', 'pracownik', 'administrator'])],
            'password' => ['nullable', Password::min(8)],
        ]);

        if (auth()->id() === $user->id_uzytkownika && $data['rola'] !== 'administrator') {
            return back()->withErrors(['rola' => 'Nie możesz odebrać samemu sobie uprawnień administratora.'])->withInput();
        }

        $user->fill([
            'imie' => $data['imie'],
            'nazwisko' => $data['nazwisko'],
            'email' => $data['email'],
            'telefon' => $data['telefon'] ?? null,
            'rola' => $data['rola'],
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'Dane użytkownika zaktualizowane.');
    }

    public function destroy(Uzytkownik $user)
    {
        if (auth()->id() === $user->id_uzytkownika) {
            return back()->withErrors(['error' => 'Nie możesz usunąć własnego konta.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Konto zostało usunięte.');
    }
}
