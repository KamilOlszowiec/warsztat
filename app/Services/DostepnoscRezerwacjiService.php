<?php
namespace App\Services;

use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Rezerwacja;
use App\Models\Uzytkownik;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DostepnoscRezerwacjiService
{
    public const KROK_MINUT = 30;
    public const STATUSY_NIEBLOKUJACE = ['odrzucona', 'anulowana'];

    public function lacznyCzas(Collection $uslugi): int
    {
        return (int) $uslugi->sum('czas_trwania');
    }

    private function wymaganeSpecjalizacje(Collection $uslugi): array
    {
        return $uslugi->pluck('id_specjalizacji')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function uprawnieniMechanicy(Collection $uslugi): array
    {
        $wymagane = $this->wymaganeSpecjalizacje($uslugi);

        if (empty($wymagane)) {
            return Uzytkownik::where('rola', 'pracownik')
                ->pluck('id_uzytkownika')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return DB::table('pracownik_specjalizacja as ps')
            ->join('uzytkownicy as u', 'u.id_uzytkownika', '=', 'ps.id_pracownika')
            ->where('u.rola', 'pracownik')
            ->whereIn('ps.id_specjalizacji', $wymagane)
            ->groupBy('ps.id_pracownika')
            ->havingRaw('COUNT(DISTINCT ps.id_specjalizacji) = ?', [count($wymagane)])
            ->pluck('ps.id_pracownika')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function czyKtosMozeWykonac(Collection $uslugi): bool
    {
        return !empty($this->uprawnieniMechanicy($uslugi));
    }

    // dane do kalendarza
    public function maksData(): string
    {
        return today()->addDays(config('warsztat.dni_naprzod'))->toDateString();
    }

    public function dniZamkniete(): array
    {
        $czynne = GodzinyPracy::where('czy_czynne', true)
            ->pluck('dzien_tygodnia')
            ->map(fn ($d) => (int) $d)
            ->all();

        return array_values(array_diff(range(1, 7), $czynne));
    }

    public function dniWolne(): array
    {
        return DzienWolny::whereBetween('data', [today()->toDateString(), $this->maksData()])
            ->pluck('data')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
    }

    public function daneKalendarza(): array
    {
        return [
            'dniZamkniete' => $this->dniZamkniete(),
            'dniWolne'     => $this->dniWolne(),
            'maksData'     => $this->maksData(),
        ];
    }

    public function listaMechanikow(Collection $uslugi): array
    {
        return $this->mechanicyDoWyboru($uslugi)
            ->map(fn ($m) => ['id' => (int) $m->id_uzytkownika, 'nazwa' => $m->imie . ' ' . $m->nazwisko])
            ->values()
            ->all();
    }

    private function mechanicyDoWyboru(Collection $uslugi): Collection
    {
        return Uzytkownik::whereIn('id_uzytkownika', $this->uprawnieniMechanicy($uslugi))
            ->orderBy('nazwisko')
            ->orderBy('imie')
            ->get(['id_uzytkownika', 'imie', 'nazwisko']);
    }

    private function ograniczDoMechanika(array $uprawnieni, ?int $idMechanika): array
    {
        if ($idMechanika === null) {
            return $uprawnieni;
        }

        return in_array($idMechanika, $uprawnieni, true) ? [$idMechanika] : [];
    }

    public function dostepneGodziny(Collection $uslugi, string $data, ?int $pomijanaRezerwacjaId = null, ?int $czas = null, ?int $idMechanika = null): array
    {
        $czas ??= $this->lacznyCzas($uslugi);
        $uprawnieni = $this->ograniczDoMechanika($this->uprawnieniMechanicy($uslugi), $idMechanika);
        $okno = $this->godzinyOtwarcia($data);

        if ($czas <= 0 || empty($uprawnieni) || $okno === null) {
            return [];
        }

        [$otwarcie, $zamkniecie] = $okno;

        // Wszystkie dane dnia pobierane raz dalej sprawdzanie w pamięci
        $kontekst = $this->kontekstDnia($otwarcie, $zamkniecie, $uprawnieni, $pomijanaRezerwacjaId);

        if ($kontekst['zmiany']->isEmpty()) {
            return [];
        }

        $godziny = [];
        $start = $otwarcie->copy();

        while ($start->copy()->addMinutes($czas)->lte($zamkniecie)) {
            $koniec = $start->copy()->addMinutes($czas);

            if ($start->gt(now()) && !empty($this->wolniMechanicy($kontekst, $start, $koniec))) {
                $godziny[] = $start->format('H:i');
            }

            $start->addMinutes(self::KROK_MINUT);
        }

        return $godziny;
    }

    private function znajdzMechanika(Collection $uslugi, Carbon $start, ?int $pomijanaRezerwacjaId = null, ?int $czas = null, ?int $idMechanika = null): ?int
    {
        $czas ??= $this->lacznyCzas($uslugi);
        $uprawnieni = $this->ograniczDoMechanika($this->uprawnieniMechanicy($uslugi), $idMechanika);

        if ($czas <= 0 || empty($uprawnieni) || !$start->gt(now())) {
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

        $kontekst = $this->kontekstDnia($otwarcie, $zamkniecie, $uprawnieni, $pomijanaRezerwacjaId);

        return $this->wolniMechanicy($kontekst, $start, $koniec)[0] ?? null;
    }

    public function utworzRezerwacje(array $dane, Collection $uslugi, Carbon $start, ?int $wybranyMechanik = null): ?Rezerwacja
    {
        return DB::transaction(function () use ($dane, $uslugi, $start, $wybranyMechanik) {
            $this->zablokujMechanikow();

            $idMechanika = $this->znajdzMechanika($uslugi, $start, null, null, $wybranyMechanik);
            if ($idMechanika === null) {
                return null;
            }

            $rezerwacja = Rezerwacja::create($dane + [
                'id_mechanika'       => $idMechanika,
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

    public function zmienTermin(Rezerwacja $rezerwacja, Carbon $start, ?int $wybranyMechanik = null): bool
    {
        return DB::transaction(function () use ($rezerwacja, $start, $wybranyMechanik) {
            $this->zablokujMechanikow();

            $rezerwacja->load('uslugi');
            $czas = $rezerwacja->laczny_czas;

            $idMechanika = $this->znajdzMechanika($rezerwacja->uslugi, $start, $rezerwacja->id_rezerwacji, $czas, $wybranyMechanik);
            if ($idMechanika === null) {
                return false;
            }

            $rezerwacja->update([
                'id_mechanika'       => $idMechanika,
                'termin_rozpoczecia' => $start,
                'termin_zakonczenia' => $start->copy()->addMinutes($czas),
            ]);

            return true;
        });
    }

    public function dostepniMechanicyDla(Rezerwacja $rezerwacja): Collection
    {
        $rezerwacja->loadMissing('uslugi');

        $start = $rezerwacja->termin_rozpoczecia;
        $koniec = $rezerwacja->termin_zakonczenia;

        $kontekst = $this->kontekstDnia(
            $start->copy()->startOfDay(),
            $start->copy()->endOfDay(),
            $this->uprawnieniMechanicy($rezerwacja->uslugi),
            $rezerwacja->id_rezerwacji
        );

        return Uzytkownik::whereIn('id_uzytkownika', $this->wolniMechanicy($kontekst, $start, $koniec))
            ->orderBy('nazwisko')
            ->get();
    }

    public function zmienMechanika(Rezerwacja $rezerwacja, int $idMechanika): bool
    {
        return DB::transaction(function () use ($rezerwacja, $idMechanika) {
            $this->zablokujMechanikow();

            if (!$this->dostepniMechanicyDla($rezerwacja)->contains('id_uzytkownika', $idMechanika)) {
                return false;
            }

            $rezerwacja->update(['id_mechanika' => $idMechanika]);

            return true;
        });
    }

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

    private function zablokujMechanikow(): void
    {
        Uzytkownik::where('rola', 'pracownik')->lockForUpdate()->get(['id_uzytkownika']);
    }

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

    // Dane potrzebne do sprawdzania terminów w danym dniu:
    private function kontekstDnia(Carbon $od, Carbon $do, array $uprawnieni, ?int $pomijanaRezerwacjaId): array
    {
        return [
            'zmiany' => $this->zmianyDnia($od->toDateString(), $uprawnieni),
            'zajete' => $this->zajeteRezerwacje($od, $do, $pomijanaRezerwacjaId),
        ];
    }

    private function wolniMechanicy(array $kontekst, Carbon $start, Carbon $koniec): array
    {
        $zajete = $kontekst['zajete'];

        return $kontekst['zmiany']
            ->filter(fn ($z) => $z->od->lte($start)
                && $z->do->gte($koniec)
                && !$this->kolizja($zajete, $z->id_mechanika, $start, $koniec))
            ->sortBy([
                fn ($a, $b) => $zajete->where('id_mechanika', $a->id_mechanika)->count()
                           <=> $zajete->where('id_mechanika', $b->id_mechanika)->count(),
                fn ($a, $b) => $a->id_mechanika <=> $b->id_mechanika,
            ])
            ->pluck('id_mechanika')
            ->values()
            ->all();
    }

    // Zmiany uprawnionych mechaników w danym dniu jako obiekty {id_mechanika, od, do}.
    private function zmianyDnia(string $data, array $uprawnieni): Collection
    {
        if (empty($uprawnieni)) {
            return collect();
        }

        return GrafikPracownika::where('data', $data)
            ->whereIn('id_pracownika', $uprawnieni)
            ->get()
            ->map(fn ($z) => (object) [
                'id_mechanika' => (int) $z->id_pracownika,
                'od'           => $z->poczatek(),
                'do'           => $z->koniec(),
            ]);
    }

    // Rezerwacje zajmujące mechaników w podanym przedziale.
    private function zajeteRezerwacje(Carbon $od, Carbon $do, ?int $pomijanaRezerwacjaId): Collection
    {
        return Rezerwacja::whereNotIn('status', self::STATUSY_NIEBLOKUJACE)
            ->whereNotNull('id_mechanika')
            ->where('termin_rozpoczecia', '<', $do)
            ->where('termin_zakonczenia', '>', $od)
            ->when($pomijanaRezerwacjaId, fn ($q) => $q->where('id_rezerwacji', '!=', $pomijanaRezerwacjaId))
            ->get(['id_rezerwacji', 'id_mechanika', 'termin_rozpoczecia', 'termin_zakonczenia']);
    }

    // Czy mechanik ma rezerwację nachodzącą na [start, koniec).
    private function kolizja(Collection $zajete, int $idMechanika, Carbon $start, Carbon $koniec): bool
    {
        return $zajete->contains(fn ($r) =>
            (int) $r->id_mechanika === $idMechanika
            && $r->termin_rozpoczecia->lt($koniec)
            && $r->termin_zakonczenia->gt($start)
        );
    }
}