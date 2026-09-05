<?php

// app/Http/Controllers/Pracownik/RezerwacjaController.php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RezerwacjaController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'oczekująca');

        $query = Rezerwacja::with(['klient', 'pojazd', 'usluga'])
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

        return back()->with('status', 'Rezerwacja została zatwierdzona.');
    }

    // app/Http/Controllers/Pracownik/RezerwacjaController.php
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
        return view('pracownik.reservations.edit-termin', ['rezerwacja' => $reservation]);
    }

    public function updateTermin(Request $request, Rezerwacja $reservation)
    {
        $data = $request->validate([
            'data' => ['required', 'date'],
            'godzina' => ['required', 'date_format:H:i'],
        ]);

        $usluga = $reservation->usluga;
        $start = Carbon::parse($data['data'] . ' ' . $data['godzina']);
        $koniec = $start->copy()->addMinutes($usluga->czas_trwania);

        $koliduje = Rezerwacja::where('id_uslugi', $usluga->id_uslugi)
            ->where('id_rezerwacji', '!=', $reservation->id_rezerwacji)
            ->whereNotIn('status', ['odrzucona', 'anulowana'])
            ->where('termin_rozpoczecia', '<', $koniec)
            ->where('termin_zakonczenia', '>', $start)
            ->count();

        if ($koliduje >= $usluga->maks_jednoczesnie) {
            return back()->withErrors(['godzina' => 'Wybrany termin koliduje z inną rezerwacją tej usługi.'])->withInput();
        }

        $reservation->update([
            'termin_rozpoczecia' => $start,
            'termin_zakonczenia' => $koniec,
        ]);

        return redirect()->route('pracownik.reservations.index')->with('status', 'Termin rezerwacji został zmieniony.');
    }
}