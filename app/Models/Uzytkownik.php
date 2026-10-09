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

    protected $fillable = ['imie', 'nazwisko', 'email', 'password', 'telefon', 'rola'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['email_verified_at' => 'datetime'];

    public function isAdmin(): bool { return $this->rola === 'administrator'; }
    public function isPracownik(): bool { return $this->rola === 'pracownik'; }
    public function isKlient(): bool { return $this->rola === 'klient'; }
    public function getRouteKeyName(): string
    {
        return 'id_uzytkownika';
    }
    public function pojazdy()
    {
        return $this->hasMany(Pojazd::class, 'id_uzytkownika', 'id_uzytkownika');
    }
}
