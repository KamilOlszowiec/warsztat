<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Uzytkownik;
use App\Services\DostepnoscRezerwacjiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GrafikController extends Controller
{
    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function index(Request $request)
    {
        $poczatek = $this->poczatekTygodnia($request->get('tydzien'));
        $koniec = $poczatek->copy()->addDays(6);

        $dni = $this->dniTygodnia($poczatek);
        $pracownicy = $this->pracownicy();

        $zmiany = GrafikPracownika::whereBetween('data', [$poczatek->toDateString(), $koniec->toDateString()])
            ->get()
            ->keyBy(fn ($z) => $z->id_pracownika . '_' . $z->data->toDateString());

        $dniWolne = DzienWolny::whereBetween('data', [$poczatek->toDateString(), $koniec->toDateString()])
            ->get()
            ->keyBy(fn ($d) => $d->data->toDateString());

        $godzinyPracy = GodzinyPracy::all()->keyBy('dzien_tygodnia');

        return view('admin.shifts.index', compact('poczatek', 'koniec', 'dni', 'pracownicy', 'zmiany', 'dniWolne', 'godzinyPracy'));
    }

    public function zapisz(Request $request)
    {
        $poczatek = $this->poczatekTygodnia($request->input('tydzien'));
        $daty = $this->dniTygodnia($poczatek)->map(fn ($d) => $d->toDateString())->all();
        $wejscie = $request->input('zmiany', []);

        $doZapisu = [];

        foreach ($this->pracownicy() as $p) {
            $id = $p->id_uzytkownika;

            foreach ($daty as $data) {
                $od = $wejscie[$id][$data]['od'] ?? null;
                $do = $wejscie[$id][$data]['do'] ?? null;

                // Oba pola puste = brak zmiany (wolne / urlop)
                if (!$od && !$do) {
                    $doZapisu[] = [$id, $data, null, null];
                    continue;
                }

                $etykieta = "{$p->imie} {$p->nazwisko}, " . Carbon::parse($data)->format('d.m');

                if (!$od || !$do) {
                    return back()->withErrors(['grafik' => "{$etykieta}: podaj obie godziny albo zostaw oba pola puste."])->withInput();
                }

                if (!preg_match('/^\d{2}:(00|30)$/', $od) || !preg_match('/^\d{2}:(00|30)$/', $do)) {
                    return back()->withErrors(['grafik' => "{$etykieta}: godziny muszą być pełne lub połówkowe (np. 08:00, 08:30)."])->withInput();
                }

                if ($od >= $do) {
                    return back()->withErrors(['grafik' => "{$etykieta}: godzina rozpoczęcia musi być wcześniejsza niż zakończenia."])->withInput();
                }

                $doZapisu[] = [$id, $data, $od, $do];
            }
        }

        DB::transaction(function () use ($doZapisu) {
            foreach ($doZapisu as [$id, $data, $od, $do]) {
                if ($od === null) {
                    GrafikPracownika::where('id_pracownika', $id)->where('data', $data)->delete();
                } else {
                    GrafikPracownika::updateOrCreate(
                        ['id_pracownika' => $id, 'data' => $data],
                        ['godzina_od' => $od, 'godzina_do' => $do]
                    );
                }
            }
        });

        return $this->powrot($poczatek, 'Grafik został zapisany.');
    }

    public function kopiuj(Request $request)
    {
        $poczatek = $this->poczatekTygodnia($request->input('tydzien'));
        $koniec = $poczatek->copy()->addDays(6);
        $idPracownikow = $this->pracownicy()->pluck('id_uzytkownika');

        $poprzednie = GrafikPracownika::whereIn('id_pracownika', $idPracownikow)
            ->whereBetween('data', [$poczatek->copy()->subWeek()->toDateString(), $poczatek->copy()->subDay()->toDateString()])
            ->get();

        if ($poprzednie->isEmpty()) {
            return back()->withErrors(['grafik' => 'Poprzedni tydzień nie ma żadnych zmian do skopiowania.']);
        }

        $wolne = $this->dniWolneTygodnia($poczatek, $koniec);

        DB::transaction(function () use ($poprzednie, $poczatek, $koniec, $wolne) {
            GrafikPracownika::whereBetween('data', [$poczatek->toDateString(), $koniec->toDateString()])->delete();

            foreach ($poprzednie as $z) {
                $nowaData = $z->data->copy()->addWeek()->toDateString();

                if (in_array($nowaData, $wolne, true)) {
                    continue;
                }

                GrafikPracownika::create([
                    'id_pracownika' => $z->id_pracownika,
                    'data'          => $nowaData,
                    'godzina_od'    => $z->godzina_od,
                    'godzina_do'    => $z->godzina_do,
                ]);
            }
        });

        return $this->powrot($poczatek, 'Skopiowano grafik z poprzedniego tygodnia.');
    }

    public function wypelnij(Request $request)
    {
        $poczatek = $this->poczatekTygodnia($request->input('tydzien'));
        $koniec = $poczatek->copy()->addDays(6);

        $godzinyPracy = GodzinyPracy::all()->keyBy('dzien_tygodnia');
        $wolne = $this->dniWolneTygodnia($poczatek, $koniec);
        $pracownicy = $this->pracownicy();

        DB::transaction(function () use ($poczatek, $godzinyPracy, $wolne, $pracownicy) {
            foreach ($this->dniTygodnia($poczatek) as $dzien) {
                $data = $dzien->toDateString();
                $godziny = $godzinyPracy->get($dzien->dayOfWeekIso);
                $czynne = $godziny && $godziny->czy_czynne && !in_array($data, $wolne, true);

                foreach ($pracownicy as $p) {
                    if (!$czynne) {
                        GrafikPracownika::where('id_pracownika', $p->id_uzytkownika)->where('data', $data)->delete();
                        continue;
                    }

                    GrafikPracownika::updateOrCreate(
                        ['id_pracownika' => $p->id_uzytkownika, 'data' => $data],
                        ['godzina_od' => $godziny->godzina_otwarcia, 'godzina_do' => $godziny->godzina_zamkniecia]
                    );
                }
            }
        });

        return $this->powrot($poczatek, 'Grafik wypełniono zgodnie z godzinami pracy warsztatu.');
    }

    private function powrot(Carbon $poczatek, string $komunikat)
    {
        $bezObsady = $this->dostepnosc->rezerwacjeBezObsady(
            $poczatek->copy()->startOfDay(),
            $poczatek->copy()->addDays(6)->endOfDay()
        );

        if ($bezObsady > 0) {
            $komunikat .= " Uwaga: {$bezObsady} przyszłych rezerwacji w tym tygodniu nie ma obsady w grafiku – zmień ich termin lub przydział mechanika.";
        }

        return redirect()
            ->route('admin.shifts.index', ['tydzien' => $poczatek->toDateString()])
            ->with('status', $komunikat);
    }

    private function poczatekTygodnia(?string $data): Carbon
    {
        try {
            $dzien = $data ? Carbon::parse($data) : Carbon::today();
        } catch (\Exception $e) {
            $dzien = Carbon::today();
        }

        return $dzien->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    private function dniTygodnia(Carbon $poczatek): Collection
    {
        return collect(range(0, 6))->map(fn ($i) => $poczatek->copy()->addDays($i));
    }

    private function dniWolneTygodnia(Carbon $poczatek, Carbon $koniec): array
    {
        return DzienWolny::whereBetween('data', [$poczatek->toDateString(), $koniec->toDateString()])
            ->get()
            ->map(fn ($d) => $d->data->toDateString())
            ->all();
    }

    private function pracownicy(): Collection
    {
        return Uzytkownik::where('rola', 'pracownik')->orderBy('nazwisko')->orderBy('imie')->get();
    }
}