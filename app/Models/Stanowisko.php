<?php
// app/Models/Stanowisko.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stanowisko extends Model
{
    protected $table = 'stanowiska';
    protected $primaryKey = 'id_stanowiska';

    protected $fillable = ['nazwa', 'typ', 'czy_aktywne'];

    protected $casts = [
        'czy_aktywne' => 'boolean',
    ];

    public const TYPY = [
        'podnosnik'     => 'Podnośnik',
        'kanal'         => 'Kanał',
        'plaskie'       => 'Stanowisko płaskie',
        'diagnostyczne' => 'Stanowisko diagnostyczne',
    ];

    /**
     * Macierz zgodności: wymaganie usługi => typy stanowisk, które je spełniają.
     * Kolejność = preferencja przydziału (najpierw najmniej "cenne" stanowisko).
     */
    public const ZGODNOSC = [
        'dowolne'       => ['plaskie', 'kanal', 'podnosnik', 'diagnostyczne'],
        'plaskie'       => ['plaskie', 'kanal', 'podnosnik'],
        'kanal'         => ['kanal', 'podnosnik'],
        'podnosnik'     => ['podnosnik'],
        'diagnostyczne' => ['diagnostyczne'],
    ];

    public function getRouteKeyName(): string
    {
        return 'id_stanowiska';
    }

    public function rezerwacje()
    {
        return $this->hasMany(Rezerwacja::class, 'id_stanowiska', 'id_stanowiska');
    }

    public function scopeAktywne($query)
    {
        return $query->where('czy_aktywne', true);
    }

    public function getTypEtykietaAttribute(): string
    {
        return self::TYPY[$this->typ] ?? $this->typ;
    }

    /**
     * Zwraca typy stanowisk akceptowalne dla WSZYSTKICH podanych wymagań
     * (część wspólna, w kolejności preferencji). Pusta tablica oznacza,
     * że tych usług nie da się wykonać podczas jednej wizyty.
     */
    public static function dozwoloneTypyDla(iterable $wymagania): array
    {
        $wynik = self::ZGODNOSC['dowolne'];

        foreach ($wymagania as $wymaganie) {
            $dozwolone = self::ZGODNOSC[$wymaganie] ?? [];
            $wynik = array_values(array_filter($wynik, fn ($typ) => in_array($typ, $dozwolone, true)));
        }

        return $wynik;
    }
}