<?php
// app/Models/Usluga.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usluga extends Model
{
    protected $table = 'uslugi';
    protected $primaryKey = 'id_uslugi';

    protected $fillable = [
        'nazwa', 'opis', 'cena', 'czas_trwania', 'id_specjalizacji', 'czy_aktywna',
    ];

    protected $casts = [
        'cena'         => 'decimal:2',
        'czas_trwania' => 'integer',
        'czy_aktywna'  => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_uslugi';
    }

    public function specjalizacja()
    {
        return $this->belongsTo(Specjalizacja::class, 'id_specjalizacji', 'id_specjalizacji');
    }

    public function rezerwacje()
    {
        return $this->belongsToMany(Rezerwacja::class, 'rezerwacja_usluga', 'id_uslugi', 'id_rezerwacji')
            ->withPivot('cena', 'czas_trwania');
    }

    public function scopeAktywne($query)
    {
        return $query->where('czy_aktywna', true);
    }
}