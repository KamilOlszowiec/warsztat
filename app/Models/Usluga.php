<?php
// app/Models/Usluga.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usluga extends Model
{
    protected $table = 'uslugi';
    protected $primaryKey = 'id_uslugi';

    protected $fillable = [
        'nazwa', 'opis', 'cena', 'czas_trwania', 'wymagany_typ_stanowiska', 'czy_aktywna',
    ];

    protected $casts = [
        'cena'         => 'decimal:2',
        'czas_trwania' => 'integer',
        'czy_aktywna'  => 'boolean',
    ];

    public const WYMAGANIA = [
        'dowolne'       => 'Dowolne stanowisko',
        'podnosnik'     => 'Podnośnik',
        'kanal'         => 'Kanał',
        'plaskie'       => 'Stanowisko płaskie',
        'diagnostyczne' => 'Stanowisko diagnostyczne',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_uslugi';
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

    public function getWymaganieEtykietaAttribute(): string
    {
        return self::WYMAGANIA[$this->wymagany_typ_stanowiska] ?? $this->wymagany_typ_stanowiska;
    }
}