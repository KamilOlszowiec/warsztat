<?php
// app/Http/Controllers/Pracownik/PrzydzialController.php

namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use App\Services\DostepnoscRezerwacjiService;
use Illuminate\Http\Request;

class PrzydzialController extends Controller
{
    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function edit(Rezerwacja $reservation)
    {
        if (!$this->moznaZmienic($reservation)) {
            return redirect()->route('pracownik.reservations.index')
                ->withErrors(['error' => 'Przydział można zmienić tylko dla oczekującej lub zatwierdzonej rezerwacji, która jeszcze się nie rozpoczęła.']);
        }

        $reservation->load(['klient', 'pojazd', 'uslugi', 'stanowisko', 'mechanik']);
        $opcje = $this->dostepnosc->opcjePrzydzialu($reservation);

        return view('pracownik.reservations.przydzial', [
            'rezerwacja' => $reservation,
            'stanowiska' => $opcje['stanowiska'],
            'mechanicy'  => $opcje['mechanicy'],
        ]);
    }

    public function update(Request $request, Rezerwacja $reservation)
    {
        if (!$this->moznaZmienic($reservation)) {
            return redirect()->route('pracownik.reservations.index')
                ->withErrors(['error' => 'Przydział można zmienić tylko dla oczekującej lub zatwierdzonej rezerwacji, która jeszcze się nie rozpoczęła.']);
        }

        $dane = $request->validate([
            'id_stanowiska' => ['required', 'integer'],
            'id_mechanika'  => ['required', 'integer'],
        ], [
            'id_stanowiska.required' => 'Wybierz stanowisko.',
            'id_mechanika.required'  => 'Wybierz mechanika.',
        ]);

        // Serwis ponownie sprawdza dostępność w transakcji z blokadą
        $zmieniono = $this->dostepnosc->zmienPrzydzial(
            $reservation,
            (int) $dane['id_stanowiska'],
            (int) $dane['id_mechanika']
        );

        if (!$zmieniono) {
            return back()
                ->withErrors(['przydzial' => 'Wybrane stanowisko lub mechanik nie są już dostępni w terminie tej wizyty. Odśwież stronę i wybierz ponownie.'])
                ->withInput();
        }

        return redirect()
            ->route('pracownik.reservations.index', ['status' => $reservation->status])
            ->with('status', 'Przydział rezerwacji został zmieniony.');
    }

    private function moznaZmienic(Rezerwacja $rezerwacja): bool
    {
        return in_array($rezerwacja->status, ['oczekująca', 'zatwierdzona'])
            && $rezerwacja->termin_rozpoczecia->isFuture();
    }
}