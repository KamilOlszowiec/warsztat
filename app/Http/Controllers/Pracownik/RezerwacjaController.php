<?php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Uzytkownik;
use App\Notifications\RezerwacjaZatwierdzona;
use App\Notifications\RezerwacjaOdrzucona;
use App\Notifications\RezerwacjaTerminZmieniony;
use App\Services\DostepnoscRezerwacjiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RezerwacjaController extends Controller
{
    private const AKTYWNE = ['oczekująca', 'zatwierdzona'];

    private const FILTRY = ['moje', 'zalegle', 'dzisiaj', 'oczekująca', 'zatwierdzona', 'zakończona', 'wszystkie'];

    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function index(Request $request)
    {
        $uzytkownik = auth()->user();
        $czyAdmin = $uzytkownik->rola === 'administrator';

        // Pracownik domyślnie widzi swoje wizyty, administrator – oczekujące na zatwierdzenie
        $status = $request->get('status', $czyAdmin ? 'oczekująca' : 'moje');
        if (!in_array($status, self::FILTRY, true) || ($status === 'moje' && $czyAdmin)) {
            $status = $czyAdmin ? 'oczekująca' : 'moje';
        }

        $query = Rezerwacja::with(['klient', 'pojazd', 'uslugi', 'mechanik'])
            ->orderBy('termin_rozpoczecia');

        if ($status === 'moje') {
            $query->where('id_mechanika', $uzytkownik->id_uzytkownika)
                ->whereIn('status', self::AKTYWNE)
                ->where('termin_rozpoczecia', '>=', today());
        } elseif ($status === 'zalegle') {
            $this->zalegle($query, $uzytkownik);
        } elseif ($status === 'dzisiaj') {
            $query->whereDate('termin_rozpoczecia', today())
                ->whereNotIn('status', ['odrzucona', 'anulowana']);
        } elseif ($status !== 'wszystkie') {
            $query->where('status', $status);
        }

        $rezerwacje = $query->get();

        $liczbaZaleglych = $this->zalegle(Rezerwacja::query(), $uzytkownik)->count();

        return view('pracownik.reservations.index', compact('rezerwacje', 'status', 'czyAdmin', 'liczbaZaleglych'));
    }

    /**
     * Wizyty, które już się skończyły, a nikt ich nie obsłużył:
     * nadal oczekują na zatwierdzenie albo nie zostały oznaczone jako zakończone.
     * Pracownik widzi tylko swoje, administrator wszystkie.
     */
    private function zalegle($query, Uzytkownik $uzytkownik)
    {
        return $query->whereIn('status', self::AKTYWNE)
            ->where('termin_zakonczenia', '<', now())
            ->when($uzytkownik->rola !== 'administrator',
                fn ($q) => $q->where('id_mechanika', $uzytkownik->id_uzytkownika));
    }

    /**
     * Pracownik może wykonywać akcje tylko na wizytach, w których jest mechanikiem.
     */
    private function wymagajUprawnien(Rezerwacja $rezerwacja): void
    {
        if (!$rezerwacja->mozeZarzadzac(auth()->user())) {
            abort(403, 'Możesz zarządzać tylko swoimi wizytami.');
        }
    }

    /**
     * Pracownik przekłada wizytę tylko na swoje wolne terminy,
     * administrator – na terminy dowolnego mechanika.
     */
    private function mechanikPrzyZmianieTerminu(): ?int
    {
        return auth()->user()->rola === 'administrator' ? null : (int) auth()->id();
    }
    /**
     * Plan dnia: kolumny mechaników, wizyty jako bloki na osi czasu.
     */
    public function plan(Request $request)
    {
        try {
            $dzien = $request->filled('data') ? Carbon::parse($request->get('data'))->startOfDay() : today();
        } catch (\Exception $e) {
            $dzien = today();
        }
        $data = $dzien->toDateString();

        $dzienWolny = DzienWolny::where('data', $data)->first();
        $godzinyPracy = GodzinyPracy::where('dzien_tygodnia', $dzien->dayOfWeekIso)->first();
        $otwarteWTenDzien = $godzinyPracy && $godzinyPracy->czy_czynne;

        // Zakres widoku: godziny otwarcia, a gdy warsztat zamknięty – 8:00–16:00
        $od = Carbon::parse($data . ' ' . ($otwarteWTenDzien ? $godzinyPracy->godzina_otwarcia : '08:00'));
        $do = Carbon::parse($data . ' ' . ($otwarteWTenDzien ? $godzinyPracy->godzina_zamkniecia : '16:00'));

        $rezerwacje = Rezerwacja::whereDate('termin_rozpoczecia', $data)
            ->whereNotIn('status', ['odrzucona', 'anulowana'])
            ->with(['klient', 'pojazd', 'uslugi', 'mechanik'])
            ->orderBy('termin_rozpoczecia')
            ->get();

        // Poszerzenie widoku, jeśli jakaś wizyta wychodzi poza godziny otwarcia
        foreach ($rezerwacje as $r) {
            if ($r->termin_rozpoczecia->lt($od)) {
                $od = $r->termin_rozpoczecia->copy()->startOfHour();
            }
            if ($r->termin_zakonczenia->gt($do)) {
                $do = $r->termin_zakonczenia->copy()->addMinutes(59)->startOfHour();
            }
        }

        // 32 px na każde 30 minut
        $pxNaMinute = 32 / 30;
        $poz = fn (Carbon $t) => max(0, (int) round(($t->getTimestamp() - $od->getTimestamp()) / 60 * $pxNaMinute));
        $wysokosc = $poz($do);

        $bloki = fn ($lista) => $lista->map(fn ($r) => [
            'rezerwacja' => $r,
            'top'        => $poz($r->termin_rozpoczecia),
            'wysokosc'   => $poz($r->termin_zakonczenia) - $poz($r->termin_rozpoczecia),
        ])->values()->all();

        $mechanicy = Uzytkownik::where('rola', 'pracownik')->orderBy('nazwisko')->orderBy('imie')->get();
        $zmiany = GrafikPracownika::where('data', $data)->get()->keyBy('id_pracownika');

        $kolumny = [];
        foreach ($mechanicy as $m) {
            $zmiana = $zmiany->get($m->id_uzytkownika);
            $poza = [];

            if (!$zmiana) {
                $poza[] = ['top' => 0, 'wysokosc' => $wysokosc, 'etykieta' => 'wolne'];
            } else {
                $start = $poz($zmiana->poczatek());
                $koniec = $poz($zmiana->koniec());
                if ($start > 0) {
                    $poza[] = ['top' => 0, 'wysokosc' => $start, 'etykieta' => 'poza zmianą'];
                }
                if ($koniec < $wysokosc) {
                    $poza[] = ['top' => $koniec, 'wysokosc' => $wysokosc - $koniec, 'etykieta' => 'poza zmianą'];
                }
            }

            $kolumny[] = [
                'tytul'     => $m->imie . ' ' . $m->nazwisko,
                'inicjaly'  => mb_strtoupper(mb_substr($m->imie, 0, 1) . mb_substr($m->nazwisko, 0, 1), 'UTF-8'),
                'podtytul'  => $zmiana ? substr($zmiana->godzina_od, 0, 5) . ' – ' . substr($zmiana->godzina_do, 0, 5) : 'wolne',
                'poza'      => $poza,
                'wizyty'    => $bloki($rezerwacje->where('id_mechanika', $m->id_uzytkownika)),
            ];
        }

        // Wizyty bez mechanika (np. po usunięciu konta) – osobna kolumna, żeby nie zginęły
        $idMechanikow = $mechanicy->pluck('id_uzytkownika');
        $bezMechanika = $rezerwacje->filter(fn ($r) => !$r->id_mechanika || !$idMechanikow->contains($r->id_mechanika));
        if ($bezMechanika->isNotEmpty()) {
            $kolumny[] = [
                'tytul'    => 'Bez mechanika',
                'inicjaly' => '?',
                'podtytul' => 'do przydzielenia',
                'poza'     => [],
                'wizyty'   => $bloki($bezMechanika),
            ];
        }

        $etykietyGodzin = [];
        for ($t = $od->copy(); $t->lt($do); $t->addHour()) {
            $etykietyGodzin[] = ['top' => $poz($t), 'etykieta' => $t->format('H:i')];
        }

        // Wybrana wizyta: z adresu, a domyślnie pierwsza własna oczekująca, potem pierwsza własna, potem pierwsza w ogóle
        $uzytkownik = auth()->user();
        $wybrana = $rezerwacje->firstWhere('id_rezerwacji', (int) $request->get('wizyta'))
            ?? $rezerwacje->first(fn ($r) => $r->status === 'oczekująca' && $r->mozeZarzadzac($uzytkownik))
            ?? $rezerwacje->first(fn ($r) => $r->mozeZarzadzac($uzytkownik))
            ?? $rezerwacje->first();

        $teraz = $dzien->isToday() && now()->between($od, $do) ? $poz(now()) : null;

        return view('pracownik.reservations.plan', compact(
            'dzien', 'dzienWolny', 'otwarteWTenDzien', 'kolumny', 'etykietyGodzin', 'wysokosc', 'wybrana', 'teraz'
        ));
    }
    public function approve(Rezerwacja $reservation)
    {
        $this->wymagajUprawnien($reservation);

        if ($reservation->status !== 'oczekująca') {
            return back()->withErrors(['error' => 'Można zatwierdzić tylko oczekujące rezerwacje.']);
        }

        $reservation->update(['status' => 'zatwierdzona']);

        $reservation->load(['uslugi', 'pojazd']);

        try {
            $reservation->klient->notify(new RezerwacjaZatwierdzona($reservation));
        } catch (\Exception $e) {
            Log::warning('Nie udało się wysłać powiadomienia email o zatwierdzeniu rezerwacji: ' . $e->getMessage());
        }

        return back()->with('status', 'Rezerwacja została zatwierdzona.');
    }

    public function reject(Request $request, Rezerwacja $reservation)
    {
        $this->wymagajUprawnien($reservation);

        if ($reservation->status !== 'oczekująca') {
            return back()->withErrors(['error' => 'Można odrzucić tylko oczekujące rezerwacje.']);
        }

        $data = $request->validate([
            'powod' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation->update([
            'status' => 'odrzucona',
            'powod_odrzucenia' => $data['powod'] ?? null,
        ]);

        $reservation->load(['uslugi', 'pojazd']);

        try {
            $reservation->klient->notify(new RezerwacjaOdrzucona($reservation));
        } catch (\Exception $e) {
            Log::warning('Nie udało się wysłać powiadomienia email o odrzuceniu rezerwacji: ' . $e->getMessage());
        }

        return back()->with('status', 'Rezerwacja została odrzucona.');
    }

    public function editTermin(Rezerwacja $reservation)
    {
        $this->wymagajUprawnien($reservation);

        $reservation->load(['klient', 'uslugi']);

        return view('pracownik.reservations.edit-termin', ['rezerwacja' => $reservation]);
    }

    /**
     * Endpoint JSON: wolne godziny dla przesuwanej rezerwacji w danym dniu.
     */
    public function dostepneTerminy(Request $request, Rezerwacja $reservation)
    {
        $this->wymagajUprawnien($reservation);

        $data = $request->validate([
            'data' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $reservation->load('uslugi');

        $godziny = $this->dostepnosc->dostepneGodziny(
            $reservation->uslugi,
            $data['data'],
            $reservation->id_rezerwacji,
            $reservation->laczny_czas,
            $this->mechanikPrzyZmianieTerminu()
        );

        return response()->json([
            'godziny'     => $godziny,
            'laczny_czas' => $reservation->laczny_czas,
            'komunikat'   => empty($godziny) ? 'Brak wolnych terminów w wybranym dniu.' : null,
        ]);
    }

    public function updateTermin(Request $request, Rezerwacja $reservation)
    {
        $this->wymagajUprawnien($reservation);

        if (!in_array($reservation->status, ['oczekująca', 'zatwierdzona'])) {
            return back()->withErrors(['error' => 'Można zmienić termin tylko oczekującej lub zatwierdzonej rezerwacji.']);
        }

        $data = $request->validate([
            'data' => ['required', 'date', 'after_or_equal:today'],
            'godzina' => ['required', 'date_format:H:i'],
        ]);

        $start = Carbon::parse($data['data'] . ' ' . $data['godzina']);

        if (!$this->dostepnosc->zmienTermin($reservation, $start, $this->mechanikPrzyZmianieTerminu())) {
            return back()
                ->withErrors(['godzina' => 'Wybrany termin jest niedostępny – poza godzinami pracy lub brak wolnego mechanika z wymaganymi specjalizacjami.'])
                ->withInput();
        }

        $reservation->load(['uslugi', 'pojazd', 'mechanik']);

        try {
            $reservation->klient->notify(new RezerwacjaTerminZmieniony($reservation));
        } catch (\Exception $e) {
            Log::warning('Nie udało się wysłać powiadomienia email o zmianie terminu: ' . $e->getMessage());
        }

        return redirect()->route('pracownik.reservations.index')->with('status', 'Termin rezerwacji został zmieniony, a klient otrzymał powiadomienie.');
    }
}