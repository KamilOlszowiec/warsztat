<?php
// app/Models/Rezerwacja.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rezerwacja extends Model
{
    protected $table = 'rezerwacje';
    protected $primaryKey = 'id_rezerwacji';

    protected $fillable = [
        'id_klienta', 'id_pojazdu', 'id_mechanika', 'id_pracownika',
        'termin_rozpoczecia', 'termin_zakonczenia', 'status', 'uwagi', 'powod_odrzucenia',
    ];

    protected $casts = [
        'termin_rozpoczecia' => 'datetime',
        'termin_zakonczenia' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_rezerwacji';
    }

    public function klient()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_klienta', 'id_uzytkownika');
    }

    public function pojazd()
    {
        return $this->belongsTo(Pojazd::class, 'id_pojazdu', 'id_pojazdu');
    }

    public function uslugi()
    {
        return $this->belongsToMany(Usluga::class, 'rezerwacja_usluga', 'id_rezerwacji', 'id_uslugi')
            ->withPivot('cena', 'czas_trwania');
    }

    // Mechanik wykonujący wizytę
    public function mechanik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_mechanika', 'id_uzytkownika');
    }

    // Pracownik, który obsłużył rezerwację (zatwierdził / odrzucił)
    public function pracownik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_pracownika', 'id_uzytkownika');
    }

    // Suma cen z chwili rezerwacji (z tabeli przestawnej)
    public function getLacznaCenaAttribute(): float
    {
        return (float) $this->uslugi->sum(fn ($u) => $u->pivot->cena);
    }

    // Suma czasu w minutach
    public function getLacznyCzasAttribute(): int
    {
        return (int) $this->uslugi->sum(fn ($u) => $u->pivot->czas_trwania);
    }

    // Np. "Wymiana oleju, Diagnostyka komputerowa" – wygodne w listach
    public function getNazwyUslugAttribute(): string
    {
        return $this->uslugi->pluck('nazwa')->implode(', ');
    }
}