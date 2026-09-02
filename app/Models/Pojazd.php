<?php
// app/Models/Pojazd.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pojazd extends Model
{
    protected $table = 'pojazdy';
    protected $primaryKey = 'id_pojazdu';

    protected $fillable = ['id_uzytkownika', 'marka', 'model', 'rok_produkcji', 'numer_rejestracyjny', 'vin'];

    public function getRouteKeyName(): string
    {
        return 'id_pojazdu';
    }

    public function uzytkownik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_uzytkownika', 'id_uzytkownika');
    }
    public function rezerwacje()
    {
        return $this->hasMany(Rezerwacja::class, 'id_pojazdu', 'id_pojazdu');
    }
}