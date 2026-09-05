<?php

// app/Models/Rezerwacja.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rezerwacja extends Model
{
    protected $table = 'rezerwacje';
    protected $primaryKey = 'id_rezerwacji';

    protected $fillable = [
        'id_klienta', 'id_pojazdu', 'id_uslugi', 'id_pracownika',
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

    public function usluga()
    {
        return $this->belongsTo(Usluga::class, 'id_uslugi', 'id_uslugi');
    }

    public function pracownik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_pracownika', 'id_uzytkownika');
    }
}