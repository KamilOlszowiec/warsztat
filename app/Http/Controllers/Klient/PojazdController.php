<?php
namespace App\Http\Controllers\Klient;

use App\Http\Controllers\Controller;
use App\Models\Pojazd;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PojazdController extends Controller
{
    public function index()
    {
        // Pojazdy z pełną historią wizyt – jedno zapytanie na relację, nie na kafelek
        $pojazdy = auth()->user()->pojazdy()
            ->with(['rezerwacje' => function ($q) {
                $q->with(['uslugi', 'mechanik'])->orderByDesc('termin_rozpoczecia');
            }])
            ->orderBy('marka')
            ->orderBy('model')
            ->get();

        return view('klient.vehicles.index', compact('pojazdy'));
    }

    public function create()
    {
        return view('klient.vehicles.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'marka' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
            'rok_produkcji' => ['nullable', 'digits:4', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'numer_rejestracyjny' => ['required', 'string', 'max:15', 'unique:pojazdy,numer_rejestracyjny'],
            'vin' => ['nullable', 'string', 'size:17'],
        ]);

        $data['id_uzytkownika'] = auth()->id();

        Pojazd::create($data);

        return redirect()->route('klient.vehicles.index')->with('status', 'Pojazd został dodany.');
    }

    public function show(Pojazd $vehicle)
    {
        $this->authorizeOwnership($vehicle);

        $rezerwacje = $vehicle->rezerwacje()
            ->with('uslugi')
            ->orderByDesc('termin_rozpoczecia')
            ->get();

        return view('klient.vehicles.show', ['pojazd' => $vehicle, 'rezerwacje' => $rezerwacje]);
    }

    public function edit(Pojazd $vehicle)
    {
        $this->authorizeOwnership($vehicle);
        return view('klient.vehicles.edit', ['pojazd' => $vehicle]);
    }

    public function update(Request $request, Pojazd $vehicle)
    {
        $this->authorizeOwnership($vehicle);

        $data = $request->validate([
            'marka' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
            'rok_produkcji' => ['nullable', 'digits:4', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'numer_rejestracyjny' => ['required', 'string', 'max:15', Rule::unique('pojazdy', 'numer_rejestracyjny')->ignore($vehicle->id_pojazdu, 'id_pojazdu')],
            'vin' => ['nullable', 'string', 'size:17'],
        ]);

        $vehicle->update($data);

        return redirect()->route('klient.vehicles.index')->with('status', 'Dane pojazdu zaktualizowane.');
    }

    public function destroy(Pojazd $vehicle)
    {
        $this->authorizeOwnership($vehicle);

        // Pojazd z historią wizyt zostaje – usunięcie skasowałoby historię napraw warsztatu
        if ($vehicle->rezerwacje()->exists()) {
            return back()->withErrors([
                'error' => "Pojazdu {$vehicle->marka} {$vehicle->model} nie można usunąć, bo ma historię wizyt w warsztacie.",
            ]);
        }

        $vehicle->delete();

        return redirect()->route('klient.vehicles.index')->with('status', 'Pojazd został usunięty.');
    }

    private function authorizeOwnership(Pojazd $vehicle): void
    {
        if ($vehicle->id_uzytkownika !== auth()->id()) {
            abort(403, 'Brak dostępu do tego pojazdu.');
        }
    }
}