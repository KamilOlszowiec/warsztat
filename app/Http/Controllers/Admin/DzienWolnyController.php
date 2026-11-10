<?php
namespace App\Http\Controllers\Admin;

use App\Enums\StatusRezerwacji;
use App\Http\Controllers\Controller;
use App\Models\DzienWolny;
use App\Models\Rezerwacja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DzienWolnyController extends Controller
{
    public function index()
    {
        $dniWolne = DzienWolny::where('data', '>=', today()->toDateString())
            ->orderBy('data')
            ->get()
            ->each(fn ($d) => $d->liczba_rezerwacji = $this->aktywneRezerwacjeDnia($d->data->toDateString()));

        // Liczba umówionych wizyt w każdym nadchodzącym dniu – do ostrzeżenia przed dodaniem dnia wolnego
        $wizytyWgDni = Rezerwacja::whereIn('status', StatusRezerwacji::aktywne())
            ->where('termin_rozpoczecia', '>=', today())
            ->selectRaw('DATE(termin_rozpoczecia) as dzien, COUNT(*) as liczba')
            ->groupBy('dzien')
            ->pluck('liczba', 'dzien');

        return view('admin.days-off.index', compact('dniWolne', 'wizytyWgDni'));
    }

    public function store(Request $request)
    {
        $dane = $request->validate([
            'data' => ['required', 'date', 'after_or_equal:today', Rule::unique('dni_wolne_warsztatu', 'data')],
            'opis' => ['nullable', 'string', 'max:100'],
        ], [
            'data.unique' => 'Ten dzień jest już oznaczony jako wolny.',
            'data.after_or_equal' => 'Dzień wolny nie może być w przeszłości.',
        ]);

        DzienWolny::create($dane);

        $komunikat = 'Dzień wolny został dodany.';
        $liczba = $this->aktywneRezerwacjeDnia($dane['data']);

        if ($liczba > 0) {
            $komunikat .= " Uwaga: w tym dniu jest {$liczba} aktywnych rezerwacji – przenieś je na inny termin albo odwołaj.";
        }

        return redirect()->route('admin.days-off.index')->with('status', $komunikat);
    }

    public function destroy(DzienWolny $dzien)
    {
        $dzien->delete();

        return redirect()->route('admin.days-off.index')->with('status', 'Dzień wolny został usunięty.');
    }

    private function aktywneRezerwacjeDnia(string $data): int
    {
        return Rezerwacja::whereDate('termin_rozpoczecia', $data)
            ->whereIn('status', StatusRezerwacji::aktywne())
            ->count();
    }
}