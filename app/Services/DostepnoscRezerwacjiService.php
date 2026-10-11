<?php
// app/Services/DostepnoscRezerwacjiService.php

namespace App\Services;

use App\Models\GodzinyPracy;
use App\Models\Rezerwacja;
use App\Models\Stanowisko;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DostepnoscRezerwacjiService
{
    public const KROK_MINUT = 30;

    private const STATUSY_NIEBLOKUJACE = ['odrzucona', 'anulowana'];

    /**
     * Łączny czas wizyty = suma czasów wybranych usług (w minutach).
     */
    public function lacznyCzas(Collection $uslugi): int
    {
        return (int) $uslugi->sum('czas_trwania');
    }

    /**
     * Typy stanowisk, na których da się wykonać WSZYSTKIE wybrane usługi.
     * Pusta tablica = usług nie można połączyć w jednej wizycie.
     */
    public function dozwoloneTypy(Collection $uslugi): array
    {
        return Stanowisko::dozwoloneTypyDla($uslugi->pluck('wymagany_typ_stanowiska'));
    }

    /**
     * Lista wolnych godzin rozpoczęcia (np. ['08:00', '08:30']) dla danego dnia.
     * $pomijanaRezerwacjaId – przy zmianie terminu rezerwacja nie koliduje sama ze sobą.
     * $czas – jawnie podany czas wizyty (przy zmianie terminu: suma z tabeli przestawnej).
     */
    public function dostepneGodziny(Collection $uslugi, string $data, ?int $pomijanaRezerwacjaId = null, ?int $czas = null): array
    {
        $czas ??= $this->lacznyCzas($uslugi);
        $typy = $this->dozwoloneTypy($uslugi);
        $okno = $this->godzinyOtwarcia($data);

        if ($czas <= 0 || empty($typy) || $okno === null) {
            return [];
        }

        [$otwarcie, $zamkniecie] = $okno;

        $stanowiska = $this->stanowiskaWgPreferencji($typy);
        if ($stanowiska->isEmpty()) {
            return [];
        }

        // Jedno zapytanie o wszystkie rezerwacje dnia, dalej sprawdzanie w pamięci
        $zajete = $this->zajeteRezerwacje($otwarcie, $zamkniecie, $pomijanaRezerwacjaId);

        $godziny = [];
        $start = $otwarcie->copy();

        while ($start->copy()->addMinutes($czas)->lte($zamkniecie)) {
            $koniec = $start->copy()->addMinutes($czas);

            if ($start->gt(now()) && $this->pierwszeWolne($stanowiska, $zajete, $start, $koniec)) {
                $godziny[] = $start->format('H:i');
            }

            $start->addMinutes(self::KROK_MINUT);
        }

        return $godziny;
    }

    /**
     * Sprawdza pojedynczy termin i zwraca stanowisko, które można przydzielić,
     * albo null, jeśli termin jest niedostępny.
     */
    public function znajdzStanowisko(Collection $uslugi, Carbon $start, ?int $pomijanaRezerwacjaId = null, ?int $czas = null): ?Stanowisko
    {
        $czas ??= $this->lacznyCzas($uslugi);
        $typy = $this->dozwoloneTypy($uslugi);

        if ($czas <= 0 || empty($typy)) {
            return null;
        }

        $koniec = $start->copy()->addMinutes($czas);

        if (!$start->gt(now()) || !$this->wGodzinachPracy($start, $koniec)) {
            return null;
        }

        $stanowiska = $this->stanowiskaWgPreferencji($typy);
        $zajete = $this->zajeteRezerwacje($start, $koniec, $pomijanaRezerwacjaId);

        return $this->pierwszeWolne($stanowiska, $zajete, $start, $koniec);
    }

    /**
     * Tworzy rezerwację w transakcji z blokadą stanowisk, co eliminuje
     * sytuację, w której dwóch klientów jednocześnie zajmuje ten sam termin.
     * Zwraca null, jeśli termin został w międzyczasie zajęty.
     */
    public function utworzRezerwacje(array $dane, Collection $uslugi, Carbon $start): ?Rezerwacja
    {
        return DB::transaction(function () use ($dane, $uslugi, $start) {
            // Blokada wierszy stanowisk – równoległe zapisy czekają na koniec tej transakcji
            Stanowisko::lockForUpdate()->get();

            $stanowisko = $this->znajdzStanowisko($uslugi, $start);
            if (!$stanowisko) {
                return null;
            }

            $rezerwacja = Rezerwacja::create($dane + [
                'id_stanowiska'      => $stanowisko->id_stanowiska,
                'termin_rozpoczecia' => $start,
                'termin_zakonczenia' => $start->copy()->addMinutes($this->lacznyCzas($uslugi)),
                'status'             => 'oczekująca',
            ]);

            // Kopia ceny i czasu z chwili rezerwacji
            $rezerwacja->uslugi()->attach(
                $uslugi->mapWithKeys(fn ($u) => [
                    $u->id_uslugi => ['cena' => $u->cena, 'czas_trwania' => $u->czas_trwania],
                ])->all()
            );

            return $rezerwacja;
        });
    }

    /**
     * Przenosi istniejącą rezerwację na nowy termin (zachowując jej czas trwania)
     * i w razie potrzeby przydziela inne stanowisko. Zwraca false, jeśli termin jest niedostępny.
     */
    public function zmienTermin(Rezerwacja $rezerwacja, Carbon $start): bool
    {
        return DB::transaction(function () use ($rezerwacja, $start) {
            Stanowisko::lockForUpdate()->get();

            $rezerwacja->load('uslugi');
            $czas = $rezerwacja->laczny_czas;

            $stanowisko = $this->znajdzStanowisko($rezerwacja->uslugi, $start, $rezerwacja->id_rezerwacji, $czas);
            if (!$stanowisko) {
                return false;
            }

            $rezerwacja->update([
                'id_stanowiska'      => $stanowisko->id_stanowiska,
                'termin_rozpoczecia' => $start,
                'termin_zakonczenia' => $start->copy()->addMinutes($czas),
            ]);

            return true;
        });
    }

    /**
     * Zwraca [otwarcie, zamkniecie] dla danego dnia albo null, jeśli warsztat jest nieczynny.
     */
    private function godzinyOtwarcia(string $data): ?array
    {
        $dzien = Carbon::parse($data);
        $godziny = GodzinyPracy::where('dzien_tygodnia', $dzien->dayOfWeekIso)->first();

        if (!$godziny || !$godziny->czy_czynne) {
            return null;
        }

        return [
            Carbon::parse($dzien->toDateString() . ' ' . $godziny->godzina_otwarcia),
            Carbon::parse($dzien->toDateString() . ' ' . $godziny->godzina_zamkniecia),
        ];
    }

    /**
     * Czy termin mieści się w godzinach pracy i zaczyna się w siatce 30-minutowej.
     */
    private function wGodzinachPracy(Carbon $start, Carbon $koniec): bool
    {
        $okno = $this->godzinyOtwarcia($start->toDateString());
        if ($okno === null) {
            return false;
        }

        [$otwarcie, $zamkniecie] = $okno;

        if ($start->lt($otwarcie) || $koniec->gt($zamkniecie)) {
            return false;
        }

        $minutyOdOtwarcia = (int) abs($otwarcie->diffInMinutes($start));

        return $minutyOdOtwarcia % self::KROK_MINUT === 0;
    }

    /**
     * Aktywne stanowiska dozwolonych typów, posortowane wg preferencji.
     */
    private function stanowiskaWgPreferencji(array $typy): Collection
    {
        return Stanowisko::aktywne()
            ->whereIn('typ', $typy)
            ->orderBy('id_stanowiska')
            ->get()
            ->sortBy(fn ($s) => array_search($s->typ, $typy, true))
            ->values();
    }

    /**
     * Rezerwacje blokujące stanowiska w podanym przedziale.
     */
    private function zajeteRezerwacje(Carbon $od, Carbon $do, ?int $pomijanaRezerwacjaId): Collection
    {
        return Rezerwacja::whereNotNull('id_stanowiska')
            ->whereNotIn('status', self::STATUSY_NIEBLOKUJACE)
            ->where('termin_rozpoczecia', '<', $do)
            ->where('termin_zakonczenia', '>', $od)
            ->when($pomijanaRezerwacjaId, fn ($q) => $q->where('id_rezerwacji', '!=', $pomijanaRezerwacjaId))
            ->get(['id_rezerwacji', 'id_stanowiska', 'termin_rozpoczecia', 'termin_zakonczenia']);
    }

    /**
     * Pierwsze stanowisko bez kolizji w przedziale [start, koniec) albo null.
     */
    private function pierwszeWolne(Collection $stanowiska, Collection $zajete, Carbon $start, Carbon $koniec): ?Stanowisko
    {
        foreach ($stanowiska as $stanowisko) {
            $kolizja = $zajete->contains(fn ($r) =>
                (int) $r->id_stanowiska === (int) $stanowisko->id_stanowiska
                && $r->termin_rozpoczecia->lt($koniec)
                && $r->termin_zakonczenia->gt($start)
            );

            if (!$kolizja) {
                return $stanowisko;
            }
        }

        return null;
    }
}