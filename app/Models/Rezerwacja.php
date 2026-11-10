<?php
namespace App\Models;

use App\Enums\StatusRezerwacji;
use Illuminate\Database\Eloquent\Model;

class Rezerwacja extends Model
{
    protected $table = 'rezerwacje';
    protected $primaryKey = 'id_rezerwacji';

    protected $fillable = [
        'id_klienta', 'id_pojazdu', 'id_mechanika',
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

    public function uslugi()
    {
        return $this->belongsToMany(Usluga::class, 'rezerwacja_usluga', 'id_rezerwacji', 'id_uslugi')
            ->withPivot('cena', 'czas_trwania');
    }

    public function mechanik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_mechanika', 'id_uzytkownika');
    }

    public function protokol()
    {
        return $this->hasOne(Protokol::class, 'id_rezerwacji', 'id_rezerwacji');
    }

    public function getLacznaCenaAttribute(): float
    {
        return (float) $this->uslugi->sum(fn ($u) => $u->pivot->cena);
    }

    public function getLacznyCzasAttribute(): int
    {
        return (int) $this->uslugi->sum(fn ($u) => $u->pivot->czas_trwania);
    }

    public function getNazwyUslugAttribute(): string
    {
        return $this->uslugi->pluck('nazwa')->implode(', ');
    }

    public function mozeZarzadzac(?Uzytkownik $uzytkownik): bool
    {
        if (!$uzytkownik) {
            return false;
        }

        return $uzytkownik->czyAdministrator()
            || (int) $this->id_mechanika === (int) $uzytkownik->id_uzytkownika;
    }

    public function statusEnum(): StatusRezerwacji
    {
        return StatusRezerwacji::from($this->status);
    }

    public function maProtokol(): bool
    {
        return $this->status === 'zakończona' && $this->protokol !== null;
    }
    
    public function getKwotaKoncowaAttribute(): float
    {
        $koszt = $this->protokol?->koszt_koncowy;

        return $koszt !== null ? (float) $koszt : $this->laczna_cena;
    }

    // Protokół może poprawić administrator zawsze, a mechanik wizyty tylko w dniu zakończenia wizyty.
    public function mozeEdytowacProtokol(?Uzytkownik $uzytkownik): bool
    {
        if (!$uzytkownik || !$this->maProtokol()) {
            return false;
        }

        if ($uzytkownik->czyAdministrator()) {
            return true;
        }

        return (int) $this->id_mechanika === (int) $uzytkownik->id_uzytkownika
            && $this->protokol->created_at->isToday();
    }

    public function poprzedniaWizytaPojazdu(bool $zPrzebiegiem = false): ?self
    {
        return self::where('id_pojazdu', $this->id_pojazdu)
            ->where('id_rezerwacji', '!=', $this->id_rezerwacji)
            ->where('status', 'zakończona')
            ->where('termin_rozpoczecia', '<', $this->termin_rozpoczecia)
            ->whereHas('protokol', fn ($q) => $zPrzebiegiem ? $q->whereNotNull('przebieg') : $q)
            ->with('protokol')
            ->orderByDesc('termin_rozpoczecia')
            ->first();
    }
}
