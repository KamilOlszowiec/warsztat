<?php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use App\Notifications\RezerwacjaZakonczona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $reservation->load(['klient', 'pojazd', 'uslugi', 'mechanik', 'protokol']);

        return view('pracownik.reservations.protokol', [
            'rezerwacja'      => $reservation,
            'protokol'        => $reservation->protokol,
            // Ostatnia wizyta – jej zalecenia; ostatni wpisany przebieg – podpowiedź przy polu
            'poprzednia'      => $reservation->poprzedniaWizytaPojazdu(),
            'poprzedniPomiar' => $reservation->poprzedniaWizytaPojazdu(zPrzebiegiem: true),
            'nowy'            => $reservation->status === 'zatwierdzona',
        ]);
    }

    public function update(Request $request, Rezerwacja $reservation)
    {
        $this->sprawdzDostep($reservation);

        $dane = $request->validate([
            'przebieg'              => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'opis_prac'             => ['required', 'string', 'max:2000'],
            'zalecenia'             => ['nullable', 'string', 'max:2000'],
            'data_nastepnej_wizyty' => ['nullable', 'date', 'after:today'],
            'koszt_koncowy'         => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'opis_prac.required'          => 'Opisz wykonane prace.',
            'data_nastepnej_wizyty.after' => 'Data kolejnej wizyty musi być w przyszłości.',
        ], [
            'przebieg' => 'przebieg', 'opis_prac' => 'opis prac', 'koszt_koncowy' => 'koszt końcowy',
        ]);

        // Przebieg jest opcjonalny. Jeśli podano mniejszy niż przy poprzednim pomiarze,
        // to zwykle literówka – wymagamy potwierdzenia
        $poprzedniPomiar = $reservation->poprzedniaWizytaPojazdu(zPrzebiegiem: true);
        if (isset($dane['przebieg']) && $poprzedniPomiar
            && (int) $dane['przebieg'] < $poprzedniPomiar->protokol->przebieg
            && !$request->boolean('potwierdz_przebieg')) {
            return back()
                ->withErrors(['przebieg' => 'Przebieg jest mniejszy niż przy poprzedniej wizycie ('
                    . number_format($poprzedniPomiar->protokol->przebieg, 0, ',', ' ') . ' km). Sprawdź wartość albo zaznacz potwierdzenie.'])
                ->withInput()
                ->with('pokaz_potwierdzenie', true);
        }

        // Puste pola = brak wartości (koszt zgodny z cennikiem, przebieg niepodany)
        $dane['przebieg'] = $request->filled('przebieg') ? $dane['przebieg'] : null;
        $dane['koszt_koncowy'] = $request->filled('koszt_koncowy') ? $dane['koszt_koncowy'] : null;

        $zakonczenie = $reservation->status === 'zatwierdzona';

        // Protokół i zmiana statusu zapisują się razem albo wcale
        DB::transaction(function () use ($reservation, $dane, $zakonczenie) {
            $reservation->protokol()->updateOrCreate(['id_rezerwacji' => $reservation->id_rezerwacji], $dane);

            if ($zakonczenie) {
                $reservation->update(['status' => 'zakończona']);
            }
        });

        if (!$zakonczenie) {
            return redirect()
                ->route('pracownik.reservations.plan', ['data' => $reservation->termin_rozpoczecia->toDateString(), 'wizyta' => $reservation->id_rezerwacji])
                ->with('status', 'Protokół został poprawiony.');
        }

        $reservation->load(['uslugi', 'pojazd', 'mechanik', 'protokol']);

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
