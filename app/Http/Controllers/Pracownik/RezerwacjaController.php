<?php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use App\Notifications\RezerwacjaZatwierdzona;
use App\Services\DostepnoscRezerwacjiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RezerwacjaController extends Controller
{
    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function index(Request $request)
    {
        $status = $request->get('status', 'oczekująca');

        $query = Rezerwacja::with(['klient', 'pojazd', 'uslugi', 'mechanik'])
            ->orderBy('termin_rozpoczecia');

        if ($status === 'dzisiaj') {
            $query->whereDate('termin_rozpoczecia', today())
                ->whereNotIn('status', ['odrzucona', 'anulowana']);
        } elseif ($status !== 'wszystkie') {
            $query->where('status', $status);
        }

        $rezerwacje = $query->get();

        return view('pracownik.reservations.index', compact('rezerwacje', 'status'));
    }

    public function approve(Rezerwacja $reservation)
    {
        if ($reservation->status !== 'oczekująca') {
            return back()->withErrors(['error' => 'Można zatwierdzić tylko oczekujące rezerwacje.']);
        }

        $reservation->update([
            'status' => 'zatwierdzona',
            'id_pracownika' => auth()->id(),
        ]);

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
        if ($reservation->status !== 'oczekująca') {
            return back()->withErrors(['error' => 'Można odrzucić tylko oczekujące rezerwacje.']);
        }

        $data = $request->validate([
            'powod' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation->update([
            'status' => 'odrzucona',
            'id_pracownika' => auth()->id(),
            'powod_odrzucenia' => $data['powod'] ?? null,
        ]);

        return back()->with('status', 'Rezerwacja została odrzucona.');
    }

    public function complete(Rezerwacja $reservation)
    {
        if ($reservation->status !== 'zatwierdzona') {
            return back()->withErrors(['error' => 'Można zakończyć tylko zatwierdzone rezerwacje.']);
        }

        $reservation->update(['status' => 'zakończona']);

        return back()->with('status', 'Rezerwacja została oznaczona jako zakończona.');
    }

    public function editTermin(Rezerwacja $reservation)
    {
        $reservation->load(['klient', 'uslugi']);

        return view('pracownik.reservations.edit-termin', ['rezerwacja' => $reservation]);
    }

    /**
     * Endpoint JSON: wolne godziny dla przesuwanej rezerwacji w danym dniu.
     */
    public function dostepneTerminy(Request $request, Rezerwacja $reservation)
    {
        $data = $request->validate([
            'data' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $reservation->load('uslugi');

        $godziny = $this->dostepnosc->dostepneGodziny(
            $reservation->uslugi,
            $data['data'],
            $reservation->id_rezerwacji,
            $reservation->laczny_czas
        );

        return response()->json([
            'godziny'     => $godziny,
            'laczny_czas' => $reservation->laczny_czas,
            'komunikat'   => empty($godziny) ? 'Brak wolnych terminów w wybranym dniu.' : null,
        ]);
    }

    public function updateTermin(Request $request, Rezerwacja $reservation)
    {
        if (!in_array($reservation->status, ['oczekująca', 'zatwierdzona'])) {
            return back()->withErrors(['error' => 'Można zmienić termin tylko oczekującej lub zatwierdzonej rezerwacji.']);
        }

        $data = $request->validate([
            'data' => ['required', 'date', 'after_or_equal:today'],
            'godzina' => ['required', 'date_format:H:i'],
        ]);

        $start = Carbon::parse($data['data'] . ' ' . $data['godzina']);

        if (!$this->dostepnosc->zmienTermin($reservation, $start)) {
            return back()
                ->withErrors(['godzina' => 'Wybrany termin jest niedostępny – poza godzinami pracy lub brak wolnego mechanika z wymaganymi specjalizacjami.'])
                ->withInput();
        }

        return redirect()->route('pracownik.reservations.index')->with('status', 'Termin rezerwacji został zmieniony.');
    }
}