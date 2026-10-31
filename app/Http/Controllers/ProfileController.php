<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        // Dane konta służbowego zmienia administrator – pracownik może zmienić tylko hasło
        if ($user->czyPracownik()) {
            return back()->withErrors([
                'error' => 'Dane konta służbowego może zmienić tylko administrator.',
            ]);
        }

        $data = $request->validate([
            'imie' => ['required', 'string', 'max:50'],
            'nazwisko' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('uzytkownicy', 'email')->ignore($user->id_uzytkownika, 'id_uzytkownika')],
            'telefon' => ['nullable', 'string', 'max:15'],
        ]);

        $emailZmieniony = $data['email'] !== $user->email;

        $user->fill($data);

        if ($emailZmieniony) {
            $user->email_verified_at = null; // wymuszamy ponowną weryfikację nowego adresu
        }

        $user->save();

        if ($emailZmieniony) {
            $user->sendEmailVerificationNotification();
            return redirect()->route('verification.notice')
                ->with('status', 'Email zmieniony — zweryfikuj nowy adres, żeby dalej korzystać z konta.');
        }

        return back()->with('status', 'Dane profilu zostały zaktualizowane.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Podane obecne hasło jest nieprawidłowe.']);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Nowe hasło musi różnić się od obecnego.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('status', 'Hasło zostało zmienione.');
    }
}