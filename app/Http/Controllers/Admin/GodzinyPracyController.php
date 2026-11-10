<?php
namespace App\Http\Controllers\Admin;

use App\Enums\StatusRezerwacji;
use App\Http\Controllers\Controller;
use App\Models\GodzinyPracy;
use App\Models\Rezerwacja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GodzinyPracyController extends Controller
{
    public const NAZWY_DNI = [
        1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek',
        5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela',
    ];

    public function index()
    {
        $godziny = GodzinyPracy::orderBy('dzien_tygodnia')->get();

        return view('admin.schedule.index', [
            'godziny'   => $godziny,
            'nazwyDni'  => self::NAZWY_DNI,
        ]);
    }

    public function update(Request $request)
    {
        $polGodziny = 'regex:/^([01]\d|2[0-3]):(00|30)$/';

        $data = $request->validate([
            'dni'                      => ['required', 'array'],
            'dni.*.id_dnia'            => ['required', 'exists:godziny_pracy,id_dnia'],
            'dni.*.czy_czynne'         => ['nullable', 'boolean'],
            'dni.*.godzina_otwarcia'   => ['nullable', $polGodziny],
            'dni.*.godzina_zamkniecia' => ['nullable', $polGodziny],
        ], [
            'dni.*.godzina_otwarcia.regex'   => 'Godziny muszą być pełne lub połówkowe (np. 08:00, 08:30).',
            'dni.*.godzina_zamkniecia.regex' => 'Godziny muszą być pełne lub połówkowe (np. 08:00, 08:30).',
        ]);

        $wpisy = GodzinyPracy::whereIn('id_dnia', array_column($data['dni'], 'id_dnia'))->get()->keyBy('id_dnia');

        // Sprawdzenie par otwarcie–zamknięcie z nazwą dnia w komunikacie
        foreach ($data['dni'] as $dzien) {
            if (empty($dzien['czy_czynne'])) {
                continue;
            }

            $nazwa = self::NAZWY_DNI[$wpisy[$dzien['id_dnia']]->dzien_tygodnia] ?? 'Dzień';

            if (empty($dzien['godzina_otwarcia']) || empty($dzien['godzina_zamkniecia'])) {
                return back()->withErrors(['godziny' => "{$nazwa}: podaj godzinę otwarcia i zamknięcia albo oznacz dzień jako zamknięty."])->withInput();
            }

            if ($dzien['godzina_otwarcia'] >= $dzien['godzina_zamkniecia']) {
                return back()->withErrors(['godziny' => "{$nazwa}: godzina zamknięcia musi być późniejsza niż otwarcia."])->withInput();
            }
        }

        DB::transaction(function () use ($data) {
            foreach ($data['dni'] as $dzien) {
                $czynne = !empty($dzien['czy_czynne']);

                GodzinyPracy::where('id_dnia', $dzien['id_dnia'])->update([
                    'czy_czynne'         => $czynne,
                    'godzina_otwarcia'   => $czynne ? $dzien['godzina_otwarcia'] : null,
                    'godzina_zamkniecia' => $czynne ? $dzien['godzina_zamkniecia'] : null,
                ]);
            }
        });

        $komunikat = 'Godziny pracy zostały zapisane.';
        $poza = $this->wizytyPozaGodzinami();
        if ($poza > 0) {
            $komunikat .= " Uwaga: {$poza} umówionych wizyt wypada poza nowymi godzinami – nie zostały przesunięte, sprawdź je na planie dnia.";
        }

        return redirect()->route('admin.schedule.index')->with('status', $komunikat);
    }

    private function wizytyPozaGodzinami(): int
    {
        $godziny = GodzinyPracy::all()->keyBy('dzien_tygodnia');

        return Rezerwacja::whereIn('status', StatusRezerwacji::aktywne())
            ->where('termin_rozpoczecia', '>', now())
            ->get(['termin_rozpoczecia', 'termin_zakonczenia'])
            ->filter(function ($r) use ($godziny) {
                $g = $godziny->get($r->termin_rozpoczecia->dayOfWeekIso);
                if (!$g || !$g->czy_czynne) {
                    return true;
                }
                return $r->termin_rozpoczecia->format('H:i') < substr($g->godzina_otwarcia, 0, 5)
                    || $r->termin_zakonczenia->format('H:i') > substr($g->godzina_zamkniecia, 0, 5);
            })
            ->count();
    }
}