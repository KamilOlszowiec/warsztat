<?php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use App\Notifications\RezerwacjaZakonczona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Zakończenie wizyty z protokołem (przebieg, wykonane prace, zalecenia, koszt końcowy)
 * oraz późniejsza poprawa protokołu.
 */
class ProtokolController extends Controller
{
    public function edit(Rezerwacja $reservation)
    {
        $this->sprawdzDostep($reservation);

        $reservation->load(['klient', 'pojazd', 'uslugi', 'mechanik']);

        return view('pracownik.reservations.protokol', [
            'rezerwacja' => $reservation,
            'poprzednia' => $reservation->poprzedniaWizytaPojazdu(),
            'nowy'       => $reservation->status === 'zatwierdzona',
        ]);
    }

    public function update(Request $request, Rezerwacja $reservation)
    {
        $this->sprawdzDostep($reservation);

        $dane = $request->validate([
            'przebieg'              => ['required', 'integer', 'min:0', 'max:3000000'],
            'opis_prac'             => ['required', 'string', 'max:2000'],
            'zalecenia'             => ['nullable', 'string', 'max:2000'],
            'data_nastepnej_wizyty' => ['nullable', 'date', 'after:today'],
            'koszt_koncowy'         => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'przebieg.required'           => 'Podaj przebieg pojazdu.',
            'opis_prac.required'          => 'Opisz wykonane prace.',
            'data_nastepnej_wizyty.after' => 'Data kolejnej wizyty musi być w przyszłości.',
        ], [
            'przebieg' => 'przebieg', 'opis_prac' => 'opis prac', 'koszt_koncowy' => 'koszt końcowy',
        ]);

        // Przebieg mniejszy niż przy poprzedniej wizycie to zwykle literówka – wymagamy potwierdzenia
        $poprzednia = $reservation->poprzedniaWizytaPojazdu();
        if ($poprzednia && (int) $dane['przebieg'] < $poprzednia->przebieg && !$request->boolean('potwierdz_przebieg')) {
            return back()
                ->withErrors(['przebieg' => 'Przebieg jest mniejszy niż przy poprzedniej wizycie ('
                    . number_format($poprzednia->przebieg, 0, ',', ' ') . ' km). Sprawdź wartość albo zaznacz potwierdzenie.'])
                ->withInput()
                ->with('pokaz_potwierdzenie', true);
        }

        // Puste pole kosztu = koszt zgodny z cennikiem
        $dane['koszt_koncowy'] = $request->filled('koszt_koncowy') ? $dane['koszt_koncowy'] : null;

        $zakonczenie = $reservation->status === 'zatwierdzona';

        if ($zakonczenie) {
            $dane['status'] = 'zakończona';
            $dane['data_zakonczenia'] = now();
        }

        $reservation->update($dane);

        if (!$zakonczenie) {
            return redirect()
                ->route('pracownik.reservations.plan', ['data' => $reservation->termin_rozpoczecia->toDateString(), 'wizyta' => $reservation->id_rezerwacji])
                ->with('status', 'Protokół został poprawiony.');
        }

        $reservation->load(['uslugi', 'pojazd', 'mechanik']);

        try {
            $reservation->klient->notify(new RezerwacjaZakonczona($reservation));
        } catch (\Exception $e) {
            Log::warning('Nie udało się wysłać powiadomienia email o zakończeniu rezerwacji: ' . $e->getMessage());
        }

        return redirect()
            ->route('pracownik.reservations.plan', ['data' => $reservation->termin_rozpoczecia->toDateString(), 'wizyta' => $reservation->id_rezerwacji])
            ->with('status', 'Wizyta została zakończona, a protokół zapisany w historii pojazdu.');
    }

    /**
     * Zakończyć można zatwierdzoną wizytę, którą się zarządza.
     * Poprawić protokół można zgodnie z zasadą z modelu (mechanik w dniu zakończenia, administrator zawsze).
     */
    private function sprawdzDostep(Rezerwacja $reservation): void
    {
        $uzytkownik = auth()->user();

        if ($reservation->status === 'zatwierdzona') {
            if (!$reservation->mozeZarzadzac($uzytkownik)) {
                abort(403, 'Możesz zakończyć tylko swoją wizytę.');
            }
            return;
        }

        if ($reservation->status === 'zakończona') {
            if (!$reservation->mozeEdytowacProtokol($uzytkownik)) {
                abort(403, 'Protokół może poprawić mechanik w dniu zakończenia wizyty, a później administrator.');
            }
            return;
        }

        abort(403, 'Protokół można wypełnić tylko dla zatwierdzonej wizyty.');
    }
}
