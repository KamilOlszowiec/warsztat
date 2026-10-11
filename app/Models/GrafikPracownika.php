<?php
// app/Models/GrafikPracownika.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class GrafikPracownika extends Model
{
    protected $table = 'grafik_pracownikow';
    protected $primaryKey = 'id_zmiany';

    protected $fillable = ['id_pracownika', 'data', 'godzina_od', 'godzina_do'];

    protected $casts = [
        'data' => 'date',
    ];

    public function pracownik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_pracownika', 'id_uzytkownika');
    }

    // Pełna data i godzina rozpoczęcia zmiany
    public function poczatek(): Carbon
    {
        return Carbon::parse($this->data->toDateString() . ' ' . $this->godzina_od);
    }

    // Pełna data i godzina zakończenia zmiany
    public function koniec(): Carbon
    {
        return Carbon::parse($this->data->toDateString() . ' ' . $this->godzina_do);
    }
}