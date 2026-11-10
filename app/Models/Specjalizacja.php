<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specjalizacja extends Model
{
    protected $table = 'specjalizacje';
    protected $primaryKey = 'id_specjalizacji';

    protected $fillable = ['nazwa'];

    public function uslugi()
    {
        return $this->hasMany(Usluga::class, 'id_specjalizacji', 'id_specjalizacji');
    }

    public function pracownicy()
    {
        return $this->belongsToMany(Uzytkownik::class, 'pracownik_specjalizacja', 'id_specjalizacji', 'id_pracownika');
    }
}