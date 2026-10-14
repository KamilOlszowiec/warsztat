<?php
// app/Http/Controllers/Pracownik/GrafikController.php

namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Rezerwacja;
use Carbon\Carbon;

class GrafikController extends Controller
{
    private const TYGODNIE = 4;

    public function index()
    {
        $user = auth()->user();

        // Grafik dotyczy tylko mechaników (admin też ma dostęp do tego panelu)
        if ($user->rola !== 'pracownik') {
            abort(403, 'Grafik jest dostępny tylko dla pracowników.');
        }

        $od = today()->startOfWeek(Carbon::MONDAY);
        $do = $od->copy()->addWeeks(self::TYGODNIE)->subDay();

        $zmiany = GrafikPracownika::where('id_pracownika', $user->id_uzytkownika)
            ->whereBetween('data', [$od->toDateString(), $do->toDateString()])
            ->get()
            ->keyBy(fn ($z) => $z->data->toDateString());

        $dniWolne = DzienWolny::whereBetween('data', [$od->toDateString(), $do->toDateString()])
            ->get()
            ->keyBy(fn ($d) => $d->data->toDateString());

        $godzinyPracy = GodzinyPracy::all()->keyBy('dzien_tygodnia');

        // Liczba moich wizyt w każdym dniu (bez odrzuconych i anulowanych)
        $wizyty = Rezerwacja::where('id_mechanika', $user->id_uzytkownika)
            ->whereNotIn('status', ['odrzucona', 'anulowana'])
            ->whereBetween('termin_rozpoczecia', [$od->copy()->startOfDay(), $do->copy()->endOfDay()])
            ->get(['termin_rozpoczecia'])
            ->groupBy(fn ($r) => $r->termin_rozpoczecia->toDateString())
            ->map->count();

        // Tygodnie z dniami
        $tygodnie = [];
        for ($t = 0; $t < self::TYGODNIE; $t++) {
            $poczatek = $od->copy()->addWeeks($t);
            $dni = [];
            $minuty = 0;

            for ($d = 0; $d < 7; $d++) {
                $dzien = $poczatek->copy()->addDays($d);
                $klucz = $dzien->toDateString();
                $zmiana = $zmiany->get($klucz);
                $godziny = $godzinyPracy->get($dzien->dayOfWeekIso);

                if ($zmiana) {
                    $minuty += $zmiana->poczatek()->diffInMinutes($zmiana->koniec());
                }

                $dni[] = [
                    'data'      => $dzien,
                    'zmiana'    => $zmiana,
                    'dzienWolny'=> $dniWolne->get($klucz),
                    'czynne'    => $godziny && $godziny->czy_czynne,
                    'wizyty'    => $wizyty->get($klucz, 0),
                ];
            }

            $tygodnie[] = [
                'poczatek' => $poczatek,
                'koniec'   => $poczatek->copy()->addDays(6),
                'godziny'  => round($minuty / 60, 1),
                'dni'      => $dni,
            ];
        }

        return view('pracownik.schedule.index', compact('tygodnie'));
    }
}