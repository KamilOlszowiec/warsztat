<?php

// app/Http/Controllers/Klient/RezerwacjaController.php
namespace App\Http\Controllers\Klient;

use App\Http\Controllers\Controller;
use App\Models\GodzinyPracy;
use App\Models\Pojazd;
use App\Models\Rezerwacja;
use App\Models\Usluga;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RezerwacjaController extends Controller
{
    public function index()
    {
        $rezerwacje = Rezerwacja::where('id_klienta', auth()->id())
            ->with(['pojazd', 'usluga'])
            ->orderByDesc('termin_rozpoczecia')
            ->get();

        return view('klient.reservations.index', compact('rezerwacje'));
    }

    public function create(Request $request)
    {
        $uslugi = Usluga::where('czy_aktywna', true)->orderBy('nazwa')->get();
        $pojazdy = auth()->user()->pojazdy()->orderBy('marka')->get();

        $wybranaUsluga = null;
        $sloty = [];

        if ($request->filled('usluga_id') && $request->filled('data')) {
            $wybranaUsluga = Usluga::find($request->usluga_id);
            if ($wybranaUsluga) {
                $sloty = $this->dostepneSloty($wybranaUsluga, $request->data);
            }
        }

        return view('klient.reservations.create', [
            'uslugi' => $uslugi,
            'pojazdy' => $pojazdy,
            'sloty' => $sloty,
            'wybranaUsluga' => $wybranaUsluga,
            'wybranaData' => $request->data,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'usluga_id' => ['required', 'exists:uslugi,id_uslugi'],
            'pojazd_id' => ['required', 'exists:pojazdy,id_pojazdu'],
            'data' => ['required', 'date', 'after_or_equal:today'],
            'godzina' => ['required', 'date_format:H:i'],
            'uwagi' => ['nullable', 'string', 'max:500'],
        ]);

        $pojazd = Pojazd::findOrFail($data['pojazd_id']);
        if ($pojazd->id_uzytkownika !== auth()->id()) {
            abort(403, 'Ten pojazd nie należy do Ciebie.');
        }

        $usluga = Usluga::findOrFail($data['usluga_id']);

        $start = Carbon::parse($data['data'] . ' ' . $data['godzina']);
        $koniec = $start->copy()->addMinutes($usluga->czas_trwania);

        // Ponowna weryfikacja kolizji po stronie serwera — nie ufamy sam samym slotom wygenerowanym wcześniej,
        // bo między wyświetleniem formularza a wysłaniem ktoś inny mógł zająć ten termin.
        if (!$this->slotWolny($usluga, $start, $koniec)) {
            return back()->withErrors(['godzina' => 'Ten termin został już zajęty. Wybierz inny.'])->withInput();
        }

        Rezerwacja::create([
            'id_klienta' => auth()->id(),
            'id_pojazdu' => $pojazd->id_pojazdu,
            'id_uslugi' => $usluga->id_uslugi,
            'termin_rozpoczecia' => $start,
            'termin_zakonczenia' => $koniec,
            'status' => 'oczekująca',
            'uwagi' => $data['uwagi'] ?? null,
        ]);

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

    /**
     * Generuje listę dostępnych godzin startowych dla danej usługi i dnia,
     * uwzględniając godziny pracy warsztatu oraz limit maks_jednoczesnie usługi.
     */
    private function dostepneSloty(Usluga $usluga, string $data): array
    {
        $dzien = Carbon::parse($data);
        $godzinyPracy = GodzinyPracy::where('dzien_tygodnia', $dzien->dayOfWeekIso)->first();

        if (!$godzinyPracy || !$godzinyPracy->czy_czynne) {
            return [];
        }

        $otwarcie = Carbon::parse($data . ' ' . $godzinyPracy->godzina_otwarcia);
        $zamkniecie = Carbon::parse($data . ' ' . $godzinyPracy->godzina_zamkniecia);

        $sloty = [];
        $obecny = $otwarcie->copy();

        while ($obecny->copy()->addMinutes($usluga->czas_trwania)->lte($zamkniecie)) {
            $start = $obecny->copy();
            $koniec = $obecny->copy()->addMinutes($usluga->czas_trwania);

            if ($start->gt(now()) && $this->slotWolny($usluga, $start, $koniec)) {
                $sloty[] = $start->format('H:i');
            }

            $obecny->addMinutes(30);
        }

        return $sloty;
    }

    /**
     * Sprawdza, czy w podanym przedziale czasowym usługa ma jeszcze wolną "pojemność"
     * (liczba nachodzących rezerwacji < maks_jednoczesnie tej usługi).
     */
    private function slotWolny(Usluga $usluga, Carbon $start, Carbon $koniec): bool
    {
        $liczbaZajetych = Rezerwacja::where('id_uslugi', $usluga->id_uslugi)
            ->whereNotIn('status', ['odrzucona', 'anulowana'])
            ->where('termin_rozpoczecia', '<', $koniec)
            ->where('termin_zakonczenia', '>', $start)
            ->count();

        return $liczbaZajetych < $usluga->maks_jednoczesnie;
    }
}