<?php

// app/Models/Usluga.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usluga extends Model
{
    protected $table = 'uslugi';
    protected $primaryKey = 'id_uslugi';

    protected $fillable = ['nazwa', 'opis', 'cena', 'czas_trwania', 'czy_aktywna', 'maks_jednoczesnie'];
    protected $casts = [
        'cena' => 'decimal:2',
        'czy_aktywna' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_uslugi';
    }
}
