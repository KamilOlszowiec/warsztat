<?php
// app/Models/Rezerwacja.php

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
        // Protokół z wizyty
        'przebieg', 'opis_prac', 'zalecenia', 'data_nastepnej_wizyty', 'koszt_koncowy', 'data_zakonczenia',
    ];

    protected $casts = [
        'termin_rozpoczecia'    => 'datetime',
        'termin_zakonczenia'    => 'datetime',
        'data_nastepnej_wizyty' => 'date',
        'data_zakonczenia'      => 'datetime',
        'koszt_koncowy'         => 'decimal:2',
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

    // Mechanik wykonujący wizytę
    public function mechanik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_mechanika', 'id_uzytkownika');
    }

    // Suma cen z chwili rezerwacji (z tabeli przestawnej)
    public function getLacznaCenaAttribute(): float
    {
        return (float) $this->uslugi->sum(fn ($u) => $u->pivot->cena);
    }

    // Suma czasu w minutach
    public function getLacznyCzasAttribute(): int
    {
        return (int) $this->uslugi->sum(fn ($u) => $u->pivot->czas_trwania);
    }

    // Np. "Wymiana oleju, Diagnostyka komputerowa" – wygodne w listach
    public function getNazwyUslugAttribute(): string
    {
        return $this->uslugi->pluck('nazwa')->implode(', ');
    }

    /**
     * Administrator zarządza wszystkimi wizytami,
     * pracownik tylko tymi, w których jest przydzielonym mechanikiem.
     */
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

    // ---------- Protokół z wizyty ----------

    public function maProtokol(): bool
    {
        return $this->status === 'zakończona' && $this->opis_prac !== null;
    }

    // Kwota do zapłaty: koszt końcowy z protokołu, a bez niego suma wg cennika z chwili rezerwacji
    public function getKwotaKoncowaAttribute(): float
    {
        return $this->koszt_koncowy !== null ? (float) $this->koszt_koncowy : $this->laczna_cena;
    }

    /**
     * Protokół może poprawić administrator zawsze, a mechanik wizyty – tylko w dniu jej zakończenia.
     */
    public function mozeEdytowacProtokol(?Uzytkownik $uzytkownik): bool
    {
        if (!$uzytkownik || $this->status !== 'zakończona') {
            return false;
        }

        if ($uzytkownik->czyAdministrator()) {
            return true;
        }

        return (int) $this->id_mechanika === (int) $uzytkownik->id_uzytkownika
            && $this->data_zakonczenia?->isToday();
    }

    /**
     * Ostatnia wcześniejsza zakończona wizyta tego samego pojazdu z wpisanym przebiegiem.
     */
    public function poprzedniaWizytaPojazdu(): ?self
    {
        return self::where('id_pojazdu', $this->id_pojazdu)
            ->where('id_rezerwacji', '!=', $this->id_rezerwacji)
            ->where('status', 'zakończona')
            ->whereNotNull('przebieg')
            ->where('termin_rozpoczecia', '<', $this->termin_rozpoczecia)
            ->orderByDesc('termin_rozpoczecia')
            ->first();
    }
}
