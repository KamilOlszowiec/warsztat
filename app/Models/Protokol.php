<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Protokol extends Model
{
    protected $table = 'protokoly';
    protected $primaryKey = 'id_protokolu';

    protected $fillable = [
        'id_rezerwacji', 'przebieg', 'opis_prac', 'zalecenia', 'data_nastepnej_wizyty', 'koszt_koncowy',
    ];

    protected $casts = [
        'przebieg'              => 'integer',
        'data_nastepnej_wizyty' => 'date',
        'koszt_koncowy'         => 'decimal:2',
    ];

    public function rezerwacja()
    {
        return $this->belongsTo(Rezerwacja::class, 'id_rezerwacji', 'id_rezerwacji');
    }
}
