<?php
namespace App\Http\Controllers\Klient;

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
    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function index()
    {
        $rezerwacje = Rezerwacja::where('id_klienta', auth()->id())
            ->with(['pojazd', 'uslugi'])
            ->orderByDesc('termin_rozpoczecia')
            ->get();

        return view('klient.reservations.index', compact('rezerwacje'));
    }

    public function create()
    {
        $uslugi = Usluga::aktywne()->orderBy('nazwa')->get();
        $pojazdy = auth()->user()->pojazdy()->orderBy('marka')->get();

        return view('klient.reservations.create', compact('uslugi', 'pojazdy'));
    }

    /**
     * Endpoint JSON: wolne godziny dla wybranych usług i dnia.
     */
    public function dostepneTerminy(Request $request)
    {
        $dane = $request->validate([
            'uslugi'   => ['required', 'array', 'min:1'],
            'uslugi.*' => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
            'data'     => ['required', 'date', 'after_or_equal:today'],
        ]);

        $uslugi = Usluga::aktywne()->whereIn('id_uslugi', $dane['uslugi'])->get();
        $lacznyCzas = $this->dostepnosc->lacznyCzas($uslugi);

        if (empty($this->dostepnosc->dozwoloneTypy($uslugi))) {
            return response()->json([
                'godziny'     => [],
                'laczny_czas' => $lacznyCzas,
                'komunikat'   => 'Wybranych usług nie da się wykonać podczas jednej wizyty – umów je osobno.',
            ]);
        }

        $godziny = $this->dostepnosc->dostepneGodziny($uslugi, $dane['data']);

        return response()->json([
            'godziny'     => $godziny,
            'laczny_czas' => $lacznyCzas,
            'komunikat'   => empty($godziny) ? 'Brak wolnych terminów w wybranym dniu.' : null,
        ]);
    }

    public function store(Request $request)
    {
        $dane = $request->validate([
            'uslugi'    => ['required', 'array', 'min:1'],
            'uslugi.*'  => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
            'pojazd_id' => ['required', 'exists:pojazdy,id_pojazdu'],
            'data'      => ['required', 'date', 'after_or_equal:today'],
            'godzina'   => ['required', 'date_format:H:i'],
            'uwagi'     => ['nullable', 'string', 'max:500'],
        ], [
            'uslugi.required' => 'Wybierz co najmniej jedną usługę.',
        ]);

        $pojazd = Pojazd::findOrFail($dane['pojazd_id']);
        if ($pojazd->id_uzytkownika !== auth()->id()) {
            abort(403, 'Ten pojazd nie należy do Ciebie.');
        }

        $uslugi = Usluga::aktywne()->whereIn('id_uslugi', $dane['uslugi'])->get();

        if (empty($this->dostepnosc->dozwoloneTypy($uslugi))) {
            return back()
                ->withErrors(['uslugi' => 'Wybranych usług nie da się wykonać podczas jednej wizyty – umów je osobno.'])
                ->withInput();
        }

        $start = Carbon::parse($dane['data'] . ' ' . $dane['godzina']);

        // Serwis ponownie sprawdza termin w transakcji – nie ufamy godzinie z formularza,
        // bo między wyświetleniem a wysłaniem ktoś inny mógł ją zająć.
        $rezerwacja = $this->dostepnosc->utworzRezerwacje([
            'id_klienta' => auth()->id(),
            'id_pojazdu' => $pojazd->id_pojazdu,
            'uwagi'      => $dane['uwagi'] ?? null,
        ], $uslugi, $start);

        if (!$rezerwacja) {
            return back()
                ->withErrors(['godzina' => 'Ten termin jest już niedostępny. Wybierz inny.'])
                ->withInput();
        }

        return redirect()->route('klient.reservations.index')
            ->with('status', 'Rezerwacja została złożona i oczekuje na potwierdzenie.');
    }

    public function cancel(Rezerwacja $reservation)
    {
        if ($reservation->id_klienta !== auth()->id()) {
            abort(403);
        }

        if (!in_array($reservation->status, ['oczekująca', 'zatwierdzona'])) {
            return back()->withErrors(['error' => 'Tej rezerwacji nie można już anulować.']);
        }

        if ($reservation->termin_rozpoczecia->isPast()) {
            return back()->withErrors(['error' => 'Nie można anulować rezerwacji, która już się rozpoczęła.']);
        }

        $reservation->update(['status' => 'anulowana']);

        return back()->with('status', 'Rezerwacja została anulowana.');
    }
}