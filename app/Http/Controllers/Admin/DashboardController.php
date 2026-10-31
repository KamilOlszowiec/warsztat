<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusRezerwacji;
use App\Http\Controllers\Controller;
use App\Models\DzienWolny;
use App\Models\GrafikPracownika;
use App\Models\Rezerwacja;
use App\Models\Usluga;
use App\Models\Uzytkownik;
use App\Services\DostepnoscRezerwacjiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const NIEBLOKUJACE = ['odrzucona', 'anulowana'];

    public function __invoke(DostepnoscRezerwacjiService $dostepnosc)
    {
        $dzis = today();
        $odTygodnia = $dzis->copy()->startOfWeek(Carbon::MONDAY);
        $doTygodnia = $odTygodnia->copy()->addDays(6)->endOfDay();

        $oblozenie = $this->oblozenie($odTygodnia, $doTygodnia);

        return view('admin.dashboard', [
            'alerty'    => $this->alerty($dostepnosc),
            'liczby'    => $this->liczby($dzis, $odTygodnia, $doTygodnia, $oblozenie),
            'oblozenie' => $oblozenie,
            'topUslugi' => $this->topUslugi(),
        ]);
    }

    /**
     * Sprawy wymagające reakcji admina – najpierw pilne, potem ostrzeżenia.
     */
    private function alerty(DostepnoscRezerwacjiService $dostepnosc): array
    {
        $alerty = [];

        // 1. Wizyty, których mechanik nie ma zmiany w grafiku (najbliższe 14 dni)
        $bezObsady = $dostepnosc->rezerwacjeBezObsady(now(), today()->addDays(14)->endOfDay());
        if ($bezObsady > 0) {
            $alerty[] = [
                'pilne' => true,
                'tytul' => $bezObsady . ' ' . self::odmiana($bezObsady, 'wizyta', 'wizyty', 'wizyt') . ' bez obsady w grafiku',
                'opis'  => 'Przypisany mechanik nie ma zmiany obejmującej wizytę (najbliższe 14 dni).',
                'link'  => route('admin.shifts.index'),
                'akcja' => 'Sprawdź grafik',
            ];
        }

        // 2. Umówione wizyty w dniach wolnych warsztatu
        $dniWolne = DzienWolny::where('data', '>=', today()->toDateString())
            ->get()
            ->map(fn ($d) => $d->data->toDateString())
            ->all();
        if (!empty($dniWolne)) {
            $wDniWolne = Rezerwacja::whereIn('status', StatusRezerwacji::aktywne())
                ->whereIn(DB::raw('DATE(termin_rozpoczecia)'), $dniWolne)
                ->count();
            if ($wDniWolne > 0) {
                $alerty[] = [
                    'pilne' => true,
                    'tytul' => $wDniWolne . ' ' . self::odmiana($wDniWolne, 'wizyta', 'wizyty', 'wizyt') . ' w dniu wolnym warsztatu',
                    'opis'  => 'Warsztat będzie wtedy zamknięty – przenieś te wizyty albo je odwołaj.',
                    'link'  => route('admin.days-off.index'),
                    'akcja' => 'Dni wolne',
                ];
            }
        }

        // 3. Aktywne usługi, których nikt nie może wykonać
        $idMechanikow = Uzytkownik::where('rola', 'pracownik')->pluck('id_uzytkownika');
        $bezMechanika = Usluga::aktywne()
            ->with('specjalizacja.pracownicy')
            ->get()
            ->filter(function ($u) use ($idMechanikow) {
                if (!$u->specjalizacja) {
                    return $idMechanikow->isEmpty();
                }
                return $u->specjalizacja->pracownicy->whereIn('id_uzytkownika', $idMechanikow)->isEmpty();
            });
        if ($bezMechanika->isNotEmpty()) {
            $alerty[] = [
                'pilne' => true,
                'tytul' => $bezMechanika->count() === 1
                    ? 'Usługa „' . $bezMechanika->first()->nazwa . '” nie ma żadnego mechanika'
                    : $bezMechanika->count() . ' usługi nie mają żadnego mechanika',
                'opis'  => $bezMechanika->count() === 1
                    ? 'Nikt nie ma wymaganej specjalizacji – klienci nie zobaczą wolnych terminów.'
                    : $bezMechanika->pluck('nazwa')->implode(', '),
                'link'  => route('admin.specializations.index'),
                'akcja' => 'Specjalizacje',
            ];
        }

        // 4. Rezerwacje czekające na zatwierdzenie
        $oczekujace = Rezerwacja::where('status', 'oczekująca')->where('termin_rozpoczecia', '>', now());
        $liczbaOczekujacych = (clone $oczekujace)->count();
        if ($liczbaOczekujacych > 0) {
            $najstarsza = (clone $oczekujace)->orderBy('created_at')->first();
            $alerty[] = [
                'pilne' => false,
                'tytul' => $liczbaOczekujacych . ' ' . self::odmiana($liczbaOczekujacych, 'rezerwacja czeka', 'rezerwacje czekają', 'rezerwacji czeka') . ' na zatwierdzenie',
                'opis'  => 'Najstarsza złożona ' . $najstarsza->created_at->diffForHumans(),
                'link'  => route('pracownik.reservations.index', ['status' => 'oczekująca']),
                'akcja' => 'Przejrzyj',
            ];
        }

        // 5. Zaległe wizyty: minęły, a nadal oczekują albo nie zostały zakończone
        $zalegle = Rezerwacja::whereIn('status', StatusRezerwacji::aktywne())
            ->where('termin_zakonczenia', '<', now())
            ->count();
        if ($zalegle > 0) {
            $alerty[] = [
                'pilne' => true,
                'tytul' => $zalegle . ' ' . self::odmiana($zalegle, 'zaległa wizyta', 'zaległe wizyty', 'zaległych wizyt'),
                'opis'  => 'Termin minął, a wizyta nadal czeka na zatwierdzenie albo nie została oznaczona jako zakończona.',
                'link'  => route('pracownik.reservations.index', ['status' => 'zalegle']),
                'akcja' => 'Przejrzyj',
            ];
        }

        // 6. Przyszłe wizyty bez przydzielonego mechanika
        $bezPrzydzialu = Rezerwacja::whereIn('status', StatusRezerwacji::aktywne())
            ->whereNull('id_mechanika')
            ->where('termin_rozpoczecia', '>', now())
            ->count();
        if ($bezPrzydzialu > 0) {
            $alerty[] = [
                'pilne' => true,
                'tytul' => $bezPrzydzialu . ' ' . self::odmiana($bezPrzydzialu, 'wizyta', 'wizyty', 'wizyt') . ' bez przydzielonego mechanika',
                'opis'  => 'Np. po usunięciu konta pracownika – przydziel mechanika albo zmień termin.',
                'link'  => route('pracownik.reservations.index', ['status' => 'wszystkie']),
                'akcja' => 'Przejrzyj',
            ];
        }

        // 7. Pusty grafik na następny tydzień
        $nastepny = today()->startOfWeek(Carbon::MONDAY)->addWeek();
        $pustyGrafik = GrafikPracownika::whereBetween('data', [$nastepny->toDateString(), $nastepny->copy()->addDays(6)->toDateString()])
            ->doesntExist();
        if ($pustyGrafik) {
            $alerty[] = [
                'pilne' => false,
                'tytul' => 'Grafik na tydzień ' . $nastepny->format('d.m') . ' – ' . $nastepny->copy()->addDays(6)->format('d.m') . ' jest pusty',
                'opis'  => 'Bez zmian w grafiku klienci nie zarezerwują wizyt w tym tygodniu.',
                'link'  => route('admin.shifts.index', ['tydzien' => $nastepny->toDateString()]),
                'akcja' => 'Uzupełnij grafik',
            ];
        }

        // Pilne na górze
        usort($alerty, fn ($a, $b) => $b['pilne'] <=> $a['pilne']);

        return $alerty;
    }

    private function liczby(Carbon $dzis, Carbon $od, Carbon $do, array $oblozenie): array
    {
        $dzisiaj = Rezerwacja::whereDate('termin_rozpoczecia', $dzis)->whereNotIn('status', self::NIEBLOKUJACE);
        $tydzien = Rezerwacja::whereBetween('termin_rozpoczecia', [$od, $do])->whereNotIn('status', self::NIEBLOKUJACE);

        $zajete = array_sum(array_column($oblozenie, 'zajeteMin'));
        $zmiany = array_sum(array_column($oblozenie, 'zmianyMin'));

        $przychod = DB::table('rezerwacja_usluga as ru')
            ->join('rezerwacje as r', 'r.id_rezerwacji', '=', 'ru.id_rezerwacji')
            ->where('r.status', 'zakończona')
            ->whereBetween('r.termin_rozpoczecia', [$dzis->copy()->startOfMonth(), $dzis->copy()->endOfMonth()])
            ->sum('ru.cena');

        return [
            'dzisWizyt'           => (clone $dzisiaj)->count(),
            'dzisOczekujacych'    => (clone $dzisiaj)->where('status', 'oczekująca')->count(),
            'tydzienWizyt'        => (clone $tydzien)->count(),
            'tydzienZakonczonych' => (clone $tydzien)->where('status', 'zakończona')->count(),
            'oblozenieProcent'    => $zmiany > 0 ? (int) round($zajete / $zmiany * 100) : null,
            'oblozenieGodziny'    => round($zajete / 60) . ' z ' . round($zmiany / 60) . ' h',
            'przychod'            => (float) $przychod,
            'miesiac'             => $dzis->translatedFormat('F'),
        ];
    }

    /**
     * Godziny wizyt każdego mechanika względem godzin jego zmian w tym tygodniu.
     */
    private function oblozenie(Carbon $od, Carbon $do): array
    {
        $mechanicy = Uzytkownik::where('rola', 'pracownik')->orderBy('nazwisko')->orderBy('imie')->get();

        $zmiany = GrafikPracownika::whereBetween('data', [$od->toDateString(), $do->toDateString()])
            ->get()
            ->groupBy('id_pracownika');

        $wizyty = Rezerwacja::whereBetween('termin_rozpoczecia', [$od, $do])
            ->whereNotIn('status', self::NIEBLOKUJACE)
            ->whereNotNull('id_mechanika')
            ->get(['id_mechanika', 'termin_rozpoczecia', 'termin_zakonczenia'])
            ->groupBy('id_mechanika');

        return $mechanicy->map(function ($m) use ($zmiany, $wizyty) {
            $zmianyMin = ($zmiany->get($m->id_uzytkownika) ?? collect())
                ->sum(fn ($z) => self::minuty($z->poczatek(), $z->koniec()));
            $zajeteMin = ($wizyty->get($m->id_uzytkownika) ?? collect())
                ->sum(fn ($r) => self::minuty($r->termin_rozpoczecia, $r->termin_zakonczenia));

            return [
                'nazwa'     => $m->imie . ' ' . $m->nazwisko,
                'zmianyMin' => $zmianyMin,
                'zajeteMin' => $zajeteMin,
                'procent'   => $zmianyMin > 0 ? (int) round($zajeteMin / $zmianyMin * 100) : null,
            ];
        })->all();
    }

    /**
     * Najczęściej rezerwowane usługi (rezerwacje złożone w ostatnich 30 dniach).
     */
    private function topUslugi()
    {
        return DB::table('rezerwacja_usluga as ru')
            ->join('rezerwacje as r', 'r.id_rezerwacji', '=', 'ru.id_rezerwacji')
            ->join('uslugi as u', 'u.id_uslugi', '=', 'ru.id_uslugi')
            ->where('r.created_at', '>=', now()->subDays(30))
            ->whereNotIn('r.status', self::NIEBLOKUJACE)
            ->groupBy('u.id_uslugi', 'u.nazwa')
            ->selectRaw('u.nazwa, COUNT(*) as liczba')
            ->orderByDesc('liczba')
            ->limit(5)
            ->get();
    }

    private static function minuty(Carbon $od, Carbon $do): int
    {
        return (int) abs(($do->getTimestamp() - $od->getTimestamp()) / 60);
    }

    private static function odmiana(int $n, string $jeden, string $kilka, string $wiele): string
    {
        if ($n === 1) {
            return $jeden;
        }
        return ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14)) ? $kilka : $wiele;
    }
}