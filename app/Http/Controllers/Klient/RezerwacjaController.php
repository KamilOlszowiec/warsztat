<?php
namespace App\Http\Controllers\Klient;

use App\Http\Controllers\Controller;
use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
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
    private const DNI_NAPRZOD = 60;

    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function index(Request $request)
    {
        $zakladka = $request->get('zakladka') === 'historia' ? 'historia' : 'nadchodzace';

        $relacje = ['pojazd', 'uslugi', 'mechanik'];
        $aktywne = ['oczekująca', 'zatwierdzona'];

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

        // Dni tygodnia (ISO 1–7), w które warsztat jest zamknięty
        $czynne = GodzinyPracy::where('czy_czynne', true)
            ->pluck('dzien_tygodnia')
            ->map(fn ($d) => (int) $d)
            ->all();
        $dniZamkniete = array_values(array_diff(range(1, 7), $czynne));

        $maksData = today()->addDays(self::DNI_NAPRZOD)->toDateString();

        $dniWolne = DzienWolny::whereBetween('data', [today()->toDateString(), $maksData])
            ->get()
            ->map(fn ($d) => $d->data->toDateString())
            ->all();

        return view('klient.reservations.create', compact('uslugi', 'pojazdy', 'dniZamkniete', 'dniWolne', 'maksData'));
    }

    /**
     * Endpoint JSON: wolne godziny dla wybranych usług i dnia.
     */
    public function dostepneTerminy(Request $request)
    {
        $dane = $request->validate([
            'uslugi'   => ['required', 'array', 'min:1'],
            'uslugi.*' => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
            'data'     => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->maksData()],
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
            'data'      => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->maksData()],
            'godzina'   => ['required', 'date_format:H:i'],
            'uwagi'     => ['nullable', 'string', 'max:500'],
        ], [
            'uslugi.required'      => 'Wybierz co najmniej jedną usługę.',
            'data.required'        => 'Wybierz dzień wizyty.',
            'data.before_or_equal' => 'Wizytę można zarezerwować najwyżej ' . self::DNI_NAPRZOD . ' dni naprzód.',
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
        ], $uslugi, $start);

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

        if (!in_array($reservation->status, ['oczekująca', 'zatwierdzona'])) {
            return back()->withErrors(['error' => 'Tej rezerwacji nie można już anulować.']);
        }

        if ($reservation->termin_rozpoczecia->isPast()) {
            return back()->withErrors(['error' => 'Nie można anulować rezerwacji, która już się rozpoczęła.']);
        }

        $reservation->update(['status' => 'anulowana']);

        return back()->with('status', 'Rezerwacja została anulowana.');
    }

    private function maksData(): string
    {
        return today()->addDays(self::DNI_NAPRZOD)->toDateString();
    }
}