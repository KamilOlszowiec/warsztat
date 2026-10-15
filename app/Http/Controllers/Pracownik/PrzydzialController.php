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
        $this->tylkoAdministrator();

        if (!$this->moznaZmienic($reservation)) {
            return redirect()->route('pracownik.reservations.index')
                ->withErrors(['error' => 'Mechanika można zmienić tylko dla oczekującej lub zatwierdzonej rezerwacji, która jeszcze się nie rozpoczęła.']);
        }

        $reservation->load(['klient', 'pojazd', 'uslugi.specjalizacja', 'mechanik']);

        return view('pracownik.reservations.przydzial', [
            'rezerwacja' => $reservation,
            'mechanicy'  => $this->dostepnosc->dostepniMechanicyDla($reservation),
        ]);
    }

    public function update(Request $request, Rezerwacja $reservation)
    {
        $this->tylkoAdministrator();

        if (!$this->moznaZmienic($reservation)) {
            return redirect()->route('pracownik.reservations.index')
                ->withErrors(['error' => 'Mechanika można zmienić tylko dla oczekującej lub zatwierdzonej rezerwacji, która jeszcze się nie rozpoczęła.']);
        }

        $dane = $request->validate([
            'id_mechanika' => ['required', 'integer'],
        ], [
            'id_mechanika.required' => 'Wybierz mechanika.',
        ]);

        // Serwis ponownie sprawdza dostępność w transakcji z blokadą
        if (!$this->dostepnosc->zmienMechanika($reservation, (int) $dane['id_mechanika'])) {
            return back()
                ->withErrors(['przydzial' => 'Wybrany mechanik nie jest już dostępny w terminie tej wizyty. Odśwież stronę i wybierz ponownie.'])
                ->withInput();
        }

        return redirect()
            ->route('pracownik.reservations.plan', [
                'data'   => $reservation->termin_rozpoczecia->toDateString(),
                'wizyta' => $reservation->id_rezerwacji,
            ])
            ->with('status', 'Mechanik został zmieniony.');
    }

    /**
     * Przydział mechaników to decyzja kierownicza – zmienia go tylko administrator.
     */
    private function tylkoAdministrator(): void
    {
        if (auth()->user()->rola !== 'administrator') {
            abort(403, 'Mechanika może zmienić tylko administrator.');
        }
    }

    private function moznaZmienic(Rezerwacja $rezerwacja): bool
    {
        return in_array($rezerwacja->status, ['oczekująca', 'zatwierdzona'])
            && $rezerwacja->termin_rozpoczecia->isFuture();
    }
}