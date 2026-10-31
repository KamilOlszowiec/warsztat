<?php
namespace App\Http\Controllers\Klient;

use App\Enums\StatusRezerwacji;
use App\Http\Controllers\Controller;
use App\Models\Pojazd;
use App\Models\Rezerwacja;
use App\Models\Usluga;
use App\Services\DostepnoscRezerwacjiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RezerwacjaController extends Controller
{
    private const KOMUNIKAT_NIEZGODNE = 'Żaden mechanik nie wykona wszystkich wybranych usług podczas jednej wizyty – umów je osobno.';

    // Jak daleko w przód klient może rezerwować

    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function index(Request $request)
    {
        $zakladka = $request->get('zakladka') === 'historia' ? 'historia' : 'nadchodzace';

        $relacje = ['pojazd', 'uslugi', 'mechanik'];
        $aktywne = StatusRezerwacji::aktywne();

        // Nadchodzące: aktywne i jeszcze się nie zaczęły – od najbliższej
        $nadchodzace = Rezerwacja::where('id_klienta', auth()->id())
            ->whereIn('status', $aktywne)
            ->where('termin_rozpoczecia', '>', now())
            ->with($relacje)
            ->orderBy('termin_rozpoczecia')
            ->get();

        // Historia: wszystko pozostałe – od najnowszej
        $historia = Rezerwacja::where('id_klienta', auth()->id())
            ->where(function ($q) use ($aktywne) {
                $q->whereNotIn('status', $aktywne)
                  ->orWhere('termin_rozpoczecia', '<=', now());
            })
            ->with($relacje)
            ->orderByDesc('termin_rozpoczecia')
            ->get();

        return view('klient.reservations.index', compact('zakladka', 'nadchodzace', 'historia'));
    }

    public function show(Rezerwacja $reservation)
    {
        if ($reservation->id_klienta !== auth()->id()) {
            abort(403);
        }

        $reservation->load(['uslugi', 'pojazd', 'mechanik']);

        return view('klient.reservations.show', ['rezerwacja' => $reservation]);
    }

    public function create()
    {
        $uslugi = Usluga::aktywne()->orderBy('nazwa')->get();
        $pojazdy = auth()->user()->pojazdy()->orderBy('marka')->get();

        return view('klient.reservations.create', [
            'uslugi'  => $uslugi,
            'pojazdy' => $pojazdy,
        ] + $this->dostepnosc->daneKalendarza());
    }

    /**
     * Endpoint JSON: mechanicy, których klient może wybrać dla zaznaczonych usług.
     */
    public function mechanicy(Request $request)
    {
        $dane = $request->validate([
            'uslugi'   => ['required', 'array', 'min:1'],
            'uslugi.*' => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
        ]);

        $uslugi = Usluga::aktywne()->whereIn('id_uslugi', $dane['uslugi'])->get();

        return response()->json([
            'mechanicy' => $this->dostepnosc->listaMechanikow($uslugi),
        ]);
    }

    /**
     * Endpoint JSON: wolne godziny dla wybranych usług i dnia,
     * opcjonalnie tylko dla wybranego mechanika.
     */
    public function dostepneTerminy(Request $request)
    {
        $dane = $request->validate([
            'uslugi'   => ['required', 'array', 'min:1'],
            'uslugi.*' => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
            'data'     => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->dostepnosc->maksData()],
            'mechanik' => ['nullable', 'integer'],
        ]);

        $uslugi = Usluga::aktywne()->whereIn('id_uslugi', $dane['uslugi'])->get();
        $lacznyCzas = $this->dostepnosc->lacznyCzas($uslugi);

        if (!$this->dostepnosc->czyKtosMozeWykonac($uslugi)) {
            return response()->json([
                'godziny'     => [],
                'laczny_czas' => $lacznyCzas,
                'komunikat'   => self::KOMUNIKAT_NIEZGODNE,
            ]);
        }

        $mechanik = isset($dane['mechanik']) ? (int) $dane['mechanik'] : null;

        $godziny = $this->dostepnosc->dostepneGodziny($uslugi, $dane['data'], null, null, $mechanik);

        return response()->json([
            'godziny'     => $godziny,
            'laczny_czas' => $lacznyCzas,
            'komunikat'   => empty($godziny)
                ? ($mechanik ? 'Wybrany mechanik nie ma wolnych terminów w tym dniu. Wybierz inny dzień albo dowolnego mechanika.' : 'Brak wolnych terminów w wybranym dniu.')
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $dane = $request->validate([
            'uslugi'    => ['required', 'array', 'min:1'],
            'uslugi.*'  => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
            'pojazd_id' => ['required', 'exists:pojazdy,id_pojazdu'],
            'data'      => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->dostepnosc->maksData()],
            'godzina'   => ['required', 'date_format:H:i'],
            'uwagi'     => ['nullable', 'string', 'max:500'],
            'id_mechanika' => ['nullable', 'integer'],
        ], [
            'uslugi.required'      => 'Wybierz co najmniej jedną usługę.',
            'data.required'        => 'Wybierz dzień wizyty.',
            'data.before_or_equal' => 'Wizytę można zarezerwować najwyżej ' . config('warsztat.dni_naprzod') . ' dni naprzód.',
            'godzina.required'     => 'Wybierz godzinę wizyty.',
            'pojazd_id.required'   => 'Wybierz pojazd.',
        ]);

        $pojazd = Pojazd::findOrFail($dane['pojazd_id']);
        if ($pojazd->id_uzytkownika !== auth()->id()) {
            abort(403, 'Ten pojazd nie należy do Ciebie.');
        }

        $uslugi = Usluga::aktywne()->whereIn('id_uslugi', $dane['uslugi'])->get();

        if (!$this->dostepnosc->czyKtosMozeWykonac($uslugi)) {
            return back()->withErrors(['uslugi' => self::KOMUNIKAT_NIEZGODNE])->withInput();
        }

        $start = Carbon::parse($dane['data'] . ' ' . $dane['godzina']);

        // Serwis ponownie sprawdza termin w transakcji – nie ufamy godzinie z formularza,
        // bo między wyświetleniem a wysłaniem ktoś inny mógł ją zająć.
        $rezerwacja = $this->dostepnosc->utworzRezerwacje([
            'id_klienta' => auth()->id(),
            'id_pojazdu' => $pojazd->id_pojazdu,
            'uwagi'      => $dane['uwagi'] ?? null,
        ], $uslugi, $start, isset($dane['id_mechanika']) ? (int) $dane['id_mechanika'] : null);

        if (!$rezerwacja) {
            return back()
                ->withErrors(['godzina' => 'Ten termin jest już niedostępny. Wybierz inny.'])
                ->withInput();
        }

        return redirect()->route('klient.reservations.show', $rezerwacja)
            ->with('status', 'Rezerwacja została złożona i oczekuje na potwierdzenie.');
    }

    public function cancel(Rezerwacja $reservation)
    {
        if ($reservation->id_klienta !== auth()->id()) {
            abort(403);
        }

        if (!in_array($reservation->status, StatusRezerwacji::aktywne())) {
            return back()->withErrors(['error' => 'Tej rezerwacji nie można już anulować.']);
        }

        if ($reservation->termin_rozpoczecia->isPast()) {
            return back()->withErrors(['error' => 'Nie można anulować rezerwacji, która już się rozpoczęła.']);
        }

        $reservation->update(['status' => 'anulowana']);

        return back()->with('status', 'Rezerwacja została anulowana.');
    }

    /**
     * Formularz zmiany terminu przez klienta.
     */
    public function editTermin(Rezerwacja $reservation)
    {
        $this->sprawdzMozliwoscZmiany($reservation);

        $reservation->load(['uslugi', 'pojazd', 'mechanik']);

        $mechanicy = $this->dostepnosc->listaMechanikow($reservation->uslugi);

        // Domyślnie obecny mechanik wizyty, jeśli nadal może ją wykonać
        $domyslnyMechanik = collect($mechanicy)->contains('id', (int) $reservation->id_mechanika)
            ? (int) $reservation->id_mechanika
            : null;

        return view('klient.reservations.edit-termin', [
            'rezerwacja'       => $reservation,
            'mechanicy'        => $mechanicy,
            'domyslnyMechanik' => $domyslnyMechanik,
        ] + $this->dostepnosc->daneKalendarza());
    }

    /**
     * Endpoint JSON: wolne godziny dla przekładanej rezerwacji klienta.
     * Rezerwacja nie koliduje sama ze sobą, a czas wizyty pochodzi z tabeli przestawnej.
     */
    public function dostepneTerminyDlaRezerwacji(Request $request, Rezerwacja $reservation)
    {
        $this->sprawdzMozliwoscZmiany($reservation);

        $dane = $request->validate([
            'data'     => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->dostepnosc->maksData()],
            'mechanik' => ['nullable', 'integer'],
        ]);

        $reservation->load('uslugi');

        $godziny = $this->dostepnosc->dostepneGodziny(
            $reservation->uslugi,
            $dane['data'],
            $reservation->id_rezerwacji,
            $reservation->laczny_czas,
            isset($dane['mechanik']) ? (int) $dane['mechanik'] : null
        );

        return response()->json([
            'godziny'     => $godziny,
            'laczny_czas' => $reservation->laczny_czas,
            'komunikat'   => empty($godziny) ? 'Brak wolnych terminów w wybranym dniu.' : null,
        ]);
    }

    /**
     * Zapis nowego terminu wybranego przez klienta.
     * Rezerwacja wraca do statusu „oczekująca” i wymaga ponownego zatwierdzenia przez warsztat.
     */
    public function updateTermin(Request $request, Rezerwacja $reservation)
    {
        $this->sprawdzMozliwoscZmiany($reservation);

        $dane = $request->validate([
            'data'    => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->dostepnosc->maksData()],
            'godzina' => ['required', 'date_format:H:i'],
            'id_mechanika' => ['nullable', 'integer'],
        ], [
            'data.required'        => 'Wybierz dzień wizyty.',
            'data.before_or_equal' => 'Wizytę można zarezerwować najwyżej ' . config('warsztat.dni_naprzod') . ' dni naprzód.',
            'godzina.required'     => 'Wybierz godzinę wizyty.',
        ]);

        $start = Carbon::parse($dane['data'] . ' ' . $dane['godzina']);

        $wybranyMechanik = isset($dane['id_mechanika']) ? (int) $dane['id_mechanika'] : null;

        if (!$this->dostepnosc->zmienTermin($reservation, $start, $wybranyMechanik)) {
            return back()
                ->withErrors(['godzina' => 'Ten termin jest już niedostępny. Wybierz inny.'])
                ->withInput();
        }

        $reservation->update(['status' => 'oczekująca']);

        return redirect()->route('klient.reservations.show', $reservation)
            ->with('status', 'Termin został zmieniony. Rezerwacja czeka na ponowne potwierdzenie warsztatu.');
    }

    /**
     * Zmienić termin można tylko we własnej, aktywnej rezerwacji, która jeszcze się nie rozpoczęła.
     */
    private function sprawdzMozliwoscZmiany(Rezerwacja $reservation): void
    {
        if ($reservation->id_klienta !== auth()->id()) {
            abort(403);
        }

        if (!in_array($reservation->status, StatusRezerwacji::aktywne())
            || !$reservation->termin_rozpoczecia->isFuture()) {
            abort(403, 'Terminu tej rezerwacji nie można już zmienić.');
        }
    }
}