<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Uzytkownik extends Authenticatable implements MustVerifyEmail, CanResetPasswordContract
{
    use Notifiable, CanResetPassword;

    protected $table = 'uzytkownicy';
    protected $primaryKey = 'id_uzytkownika';

    protected $fillable = ['imie', 'nazwisko', 'email', 'password', 'telefon', 'rola', 'email_verified_at'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['email_verified_at' => 'datetime'];

    public function czyAdministrator(): bool { return $this->rola === 'administrator'; }
    public function czyPracownik(): bool { return $this->rola === 'pracownik'; }
    public function czyKlient(): bool { return $this->rola === 'klient'; }

    public function getRouteKeyName(): string
    {
        return 'id_uzytkownika';
    }

    public function pojazdy()
    {
        return $this->hasMany(Pojazd::class, 'id_uzytkownika', 'id_uzytkownika');
    }

    // Specjalizacje pracownika (mechanika)
    public function specjalizacje()
    {
        return $this->belongsToMany(Specjalizacja::class, 'pracownik_specjalizacja', 'id_pracownika', 'id_specjalizacji');
    }

    // Rezerwacje złożone przez klienta
    public function rezerwacjeKlienta()
    {
        return $this->hasMany(Rezerwacja::class, 'id_klienta', 'id_uzytkownika');
    }

    // Wizyty, które wykonuje mechanik
    public function wizytyMechanika()
    {
        return $this->hasMany(Rezerwacja::class, 'id_mechanika', 'id_uzytkownika');
    }

    // Zmiany pracownika w grafiku
    public function zmiany()
    {
        return $this->hasMany(GrafikPracownika::class, 'id_pracownika', 'id_uzytkownika');
    }
}