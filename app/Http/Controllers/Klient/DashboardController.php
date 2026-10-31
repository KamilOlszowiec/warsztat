<?php
// app/Http/Controllers/Klient/DashboardController.php

namespace App\Http\Controllers\Klient;

use App\Enums\StatusRezerwacji;
use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;

class DashboardController extends Controller
{

    public function __invoke()
    {
        $user = auth()->user();
        $relacje = ['uslugi', 'pojazd', 'mechanik'];

        // Nadchodzące wizyty (od najbliższej)
        $nadchodzace = Rezerwacja::where('id_klienta', $user->id_uzytkownika)
            ->whereIn('status', StatusRezerwacji::aktywne())
            ->where('termin_rozpoczecia', '>', now())
            ->with($relacje)
            ->orderBy('termin_rozpoczecia')
            ->get();

        $najblizsza = $nadchodzace->first();

        // Ile dni do najbliższej wizyty: "dziś", "jutro", "za X dni"
        $kiedy = null;
        if ($najblizsza) {
            $dni = (int) today()->diffInDays($najblizsza->termin_rozpoczecia->copy()->startOfDay());
            $kiedy = match (true) {
                $dni <= 0 => 'dziś',
                $dni === 1 => 'jutro',
                default => "za {$dni} dni",
            };
        }

        // Ostatnie wizyty: przeszłe albo już nieaktywne (zakończone, odrzucone, anulowane)
        $ostatnie = Rezerwacja::where('id_klienta', $user->id_uzytkownika)
            ->where(function ($q) {
                $q->where('termin_rozpoczecia', '<=', now())
                  ->orWhereNotIn('status', StatusRezerwacji::aktywne());
            })
            ->with($relacje)
            ->orderByDesc('termin_rozpoczecia')
            ->limit(4)
            ->get();

        // Lista: pozostałe nadchodzące (bez wizyty z banera), dopełnione ostatnimi – razem max 4
        $lista = $nadchodzace->slice(1)->concat($ostatnie)->take(4)->values();

        $zakonczone = Rezerwacja::where('id_klienta', $user->id_uzytkownika)
            ->where('status', 'zakończona')
            ->orderByDesc('termin_rozpoczecia');

        $pojazdy = $user->pojazdy()
            ->withCount(['rezerwacje as liczba_wizyt' => fn ($q) => $q->where('status', 'zakończona')])
            ->orderBy('marka')
            ->orderBy('model')
            ->get();

        return view('klient.dashboard', [
            'najblizsza'       => $najblizsza,
            'kiedy'            => $kiedy,
            'lista'            => $lista,
            'pojazdy'          => $pojazdy,
            'liczbaAktywnych'  => $nadchodzace->count(),
            'liczbaOczekujacych' => $nadchodzace->where('status', 'oczekująca')->count(),
            'liczbaZakonczonych' => (clone $zakonczone)->count(),
            'ostatniaWizyta'   => (clone $zakonczone)->first(),
        ]);
    }
}