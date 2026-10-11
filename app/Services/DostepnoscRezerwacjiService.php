<?php
// app/Services/DostepnoscRezerwacjiService.php

namespace App\Services;

use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Rezerwacja;
use App\Models\Stanowisko;
use App\Models\Uzytkownik;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DostepnoscRezerwacjiService
{
    public const KROK_MINUT = 30;

    public const STATUSY_NIEBLOKUJACE = ['odrzucona', 'anulowana'];

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

        // Wszystkie dane dnia pobierane raz, dalej sprawdzanie w pamięci
        $kontekst = $this->kontekstDnia($otwarcie, $zamkniecie, $typy, $pomijanaRezerwacjaId);

        if ($kontekst['stanowiska']->isEmpty() || $kontekst['zmiany']->isEmpty()) {
            return [];
        }

        $godziny = [];
        $start = $otwarcie->copy();

        while ($start->copy()->addMinutes($czas)->lte($zamkniecie)) {
            $koniec = $start->copy()->addMinutes($czas);

            if ($start->gt(now()) && $this->przydzial($kontekst, $start, $koniec) !== null) {
                $godziny[] = $start->format('H:i');
            }

            $start->addMinutes(self::KROK_MINUT);
        }

        return $godziny;
    }

    /**
     * Sprawdza pojedynczy termin. Zwraca ['stanowisko' => Stanowisko, 'id_mechanika' => int]
     * albo null, jeśli termin jest niedostępny.
     */
    public function znajdzPrzydzial(Collection $uslugi, Carbon $start, ?int $pomijanaRezerwacjaId = null, ?int $czas = null): ?array
    {
        $czas ??= $this->lacznyCzas($uslugi);
        $typy = $this->dozwoloneTypy($uslugi);

        if ($czas <= 0 || empty($typy) || !$start->gt(now())) {
            return null;
        }

        $okno = $this->godzinyOtwarcia($start->toDateString());
        if ($okno === null) {
            return null;
        }

        [$otwarcie, $zamkniecie] = $okno;
        $koniec = $start->copy()->addMinutes($czas);

        if ($start->lt($otwarcie) || $koniec->gt($zamkniecie)) {
            return null;
        }

        // Start musi wypadać w siatce 30-minutowej liczonej od otwarcia
        if ((int) abs($otwarcie->diffInMinutes($start)) % self::KROK_MINUT !== 0) {
            return null;
        }

        $kontekst = $this->kontekstDnia($otwarcie, $zamkniecie, $typy, $pomijanaRezerwacjaId);

        return $this->przydzial($kontekst, $start, $koniec);
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

            $przydzial = $this->znajdzPrzydzial($uslugi, $start);
            if (!$przydzial) {
                return null;
            }

            $rezerwacja = Rezerwacja::create($dane + [
                'id_stanowiska'      => $przydzial['stanowisko']->id_stanowiska,
                'id_mechanika'       => $przydzial['id_mechanika'],
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
     * i przydziela stanowisko oraz mechanika. Zwraca false, jeśli termin jest niedostępny.
     */
    public function zmienTermin(Rezerwacja $rezerwacja, Carbon $start): bool
    {
        return DB::transaction(function () use ($rezerwacja, $start) {
            Stanowisko::lockForUpdate()->get();

            $rezerwacja->load('uslugi');
            $czas = $rezerwacja->laczny_czas;

            $przydzial = $this->znajdzPrzydzial($rezerwacja->uslugi, $start, $rezerwacja->id_rezerwacji, $czas);
            if (!$przydzial) {
                return false;
            }

            $rezerwacja->update([
                'id_stanowiska'      => $przydzial['stanowisko']->id_stanowiska,
                'id_mechanika'       => $przydzial['id_mechanika'],
                'termin_rozpoczecia' => $start,
                'termin_zakonczenia' => $start->copy()->addMinutes($czas),
            ]);

            return true;
        });
    }

    /**
     * Stanowiska i mechanicy, których można przypisać do rezerwacji w jej obecnym terminie
     * (używane przez pracownika przy zmianie przydziału).
     */
    public function opcjePrzydzialu(Rezerwacja $rezerwacja): array
    {
        $rezerwacja->loadMissing('uslugi');

        $start = $rezerwacja->termin_rozpoczecia;
        $koniec = $rezerwacja->termin_zakonczenia;
        $typy = $this->dozwoloneTypy($rezerwacja->uslugi);

        $kontekst = $this->kontekstDnia(
            $start->copy()->startOfDay(),
            $start->copy()->endOfDay(),
            $typy,
            $rezerwacja->id_rezerwacji
        );

        $stanowiska = $kontekst['stanowiska']
            ->filter(fn ($s) => !$this->kolizja($kontekst['zajete'], 'id_stanowiska', $s->id_stanowiska, $start, $koniec))
            ->values();

        $mechanicy = Uzytkownik::whereIn('id_uzytkownika', $this->wolniMechanicy($kontekst, $start, $koniec))
            ->orderBy('nazwisko')
            ->get();

        return ['stanowiska' => $stanowiska, 'mechanicy' => $mechanicy];
    }

    /**
     * Ręczna zmiana stanowiska i mechanika dla rezerwacji. Zwraca false,
     * jeśli wybrane stanowisko lub mechanik nie są dostępni w terminie wizyty.
     */
    public function zmienPrzydzial(Rezerwacja $rezerwacja, int $idStanowiska, int $idMechanika): bool
    {
        return DB::transaction(function () use ($rezerwacja, $idStanowiska, $idMechanika) {
            Stanowisko::lockForUpdate()->get();

            $opcje = $this->opcjePrzydzialu($rezerwacja);

            if (!$opcje['stanowiska']->contains('id_stanowiska', $idStanowiska)
                || !$opcje['mechanicy']->contains('id_uzytkownika', $idMechanika)) {
                return false;
            }

            $rezerwacja->update([
                'id_stanowiska' => $idStanowiska,
                'id_mechanika'  => $idMechanika,
            ]);

            return true;
        });
    }

    /**
     * Liczba przyszłych aktywnych rezerwacji w przedziale dat, których mechanik
     * nie ma zmiany obejmującej całą wizytę (np. po zmianie grafiku lub urlopie).
     */
    public function rezerwacjeBezObsady(Carbon $od, Carbon $do): int
    {
        $odKiedy = $od->gt(now()) ? $od : now();

        $rezerwacje = Rezerwacja::whereNotNull('id_mechanika')
            ->whereNotIn('status', [...self::STATUSY_NIEBLOKUJACE, 'zakończona'])
            ->where('termin_rozpoczecia', '>=', $odKiedy)
            ->where('termin_rozpoczecia', '<=', $do)
            ->get(['id_rezerwacji', 'id_mechanika', 'termin_rozpoczecia', 'termin_zakonczenia']);

        if ($rezerwacje->isEmpty()) {
            return 0;
        }

        $zmiany = GrafikPracownika::whereBetween('data', [$od->toDateString(), $do->toDateString()])->get();

        return $rezerwacje->filter(fn ($r) => !$zmiany->contains(fn ($z) =>
            (int) $z->id_pracownika === (int) $r->id_mechanika
            && $z->data->isSameDay($r->termin_rozpoczecia)
            && $z->poczatek()->lte($r->termin_rozpoczecia)
            && $z->koniec()->gte($r->termin_zakonczenia)
        ))->count();
    }

    /**
     * Zwraca [otwarcie, zamkniecie] dla danego dnia albo null, jeśli warsztat
     * jest nieczynny w ten dzień tygodnia lub dzień jest dniem wolnym.
     */
    private function godzinyOtwarcia(string $data): ?array
    {
        $dzien = Carbon::parse($data);

        if (DzienWolny::where('data', $dzien->toDateString())->exists()) {
            return null;
        }

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
     * Dane potrzebne do sprawdzania terminów w danym dniu.
     */
    private function kontekstDnia(Carbon $od, Carbon $do, array $typy, ?int $pomijanaRezerwacjaId): array
    {
        return [
            'stanowiska' => $this->stanowiskaWgPreferencji($typy),
            'zmiany'     => $this->zmianyDnia($od->toDateString()),
            'zajete'     => $this->zajeteRezerwacje($od, $do, $pomijanaRezerwacjaId),
        ];
    }

    /**
     * Pierwsze wolne stanowisko i najmniej obciążony wolny mechanik albo null.
     */
    private function przydzial(array $kontekst, Carbon $start, Carbon $koniec): ?array
    {
        $stanowisko = $kontekst['stanowiska']->first(
            fn ($s) => !$this->kolizja($kontekst['zajete'], 'id_stanowiska', $s->id_stanowiska, $start, $koniec)
        );

        if (!$stanowisko) {
            return null;
        }

        $mechanicy = $this->wolniMechanicy($kontekst, $start, $koniec);

        if (empty($mechanicy)) {
            return null;
        }

        return ['stanowisko' => $stanowisko, 'id_mechanika' => $mechanicy[0]];
    }

    /**
     * Id mechaników, których zmiana obejmuje całą wizytę i którzy nie mają w tym czasie
     * innej wizyty, posortowane od najmniej obciążonego w danym dniu.
     */
    private function wolniMechanicy(array $kontekst, Carbon $start, Carbon $koniec): array
    {
        $zajete = $kontekst['zajete'];

        return $kontekst['zmiany']
            ->filter(fn ($z) => $z->od->lte($start)
                && $z->do->gte($koniec)
                && !$this->kolizja($zajete, 'id_mechanika', $z->id_mechanika, $start, $koniec))
            ->sortBy([
                fn ($a, $b) => $zajete->where('id_mechanika', $a->id_mechanika)->count()
                           <=> $zajete->where('id_mechanika', $b->id_mechanika)->count(),
                fn ($a, $b) => $a->id_mechanika <=> $b->id_mechanika,
            ])
            ->pluck('id_mechanika')
            ->values()
            ->all();
    }

    /**
     * Zmiany mechaników w danym dniu jako obiekty {id_mechanika, od, do}.
     */
    private function zmianyDnia(string $data): Collection
    {
        return GrafikPracownika::where('data', $data)
            ->whereHas('pracownik', fn ($q) => $q->where('rola', 'pracownik'))
            ->get()
            ->map(fn ($z) => (object) [
                'id_mechanika' => (int) $z->id_pracownika,
                'od'           => $z->poczatek(),
                'do'           => $z->koniec(),
            ]);
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
     * Rezerwacje blokujące stanowiska i mechaników w podanym przedziale.
     */
    private function zajeteRezerwacje(Carbon $od, Carbon $do, ?int $pomijanaRezerwacjaId): Collection
    {
        return Rezerwacja::whereNotIn('status', self::STATUSY_NIEBLOKUJACE)
            ->where('termin_rozpoczecia', '<', $do)
            ->where('termin_zakonczenia', '>', $od)
            ->when($pomijanaRezerwacjaId, fn ($q) => $q->where('id_rezerwacji', '!=', $pomijanaRezerwacjaId))
            ->get(['id_rezerwacji', 'id_stanowiska', 'id_mechanika', 'termin_rozpoczecia', 'termin_zakonczenia']);
    }

    /**
     * Czy zasób (stanowisko lub mechanik) ma rezerwację nachodzącą na [start, koniec).
     */
    private function kolizja(Collection $zajete, string $pole, int $id, Carbon $start, Carbon $koniec): bool
    {
        return $zajete->contains(fn ($r) =>
            (int) $r->{$pole} === $id
            && $r->termin_rozpoczecia->lt($koniec)
            && $r->termin_zakonczenia->gt($start)
        );
    }
}