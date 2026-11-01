<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GodzinyPracy extends Model
{
    protected $table = 'godziny_pracy';
    protected $primaryKey = 'id_dnia';

    protected $fillable = ['dzien_tygodnia', 'godzina_otwarcia', 'godzina_zamkniecia', 'czy_czynne'];
    protected $casts = ['czy_czynne' => 'boolean'];

    public const DNI = [
        1 => 'Poniedziałek',
        2 => 'Wtorek',
        3 => 'Środa',
        4 => 'Czwartek',
        5 => 'Piątek',
        6 => 'Sobota',
        7 => 'Niedziela',
    ];
}