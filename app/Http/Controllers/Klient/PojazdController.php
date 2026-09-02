<?php

// app/Http/Controllers/Klient/PojazdController.php
namespace App\Http\Controllers\Klient;

use App\Http\Controllers\Controller;
use App\Models\Pojazd;
use Illuminate\Http\Request;

class PojazdController extends Controller
{
    public function index()
    {
        $pojazdy = auth()->user()->pojazdy()->orderBy('marka')->get();
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
            'numer_rejestracyjny' => ['required', 'string', 'max:15'],
            'vin' => ['nullable', 'string', 'size:17'],
        ]);

        $data['id_uzytkownika'] = auth()->id();

        Pojazd::create($data);

        return redirect()->route('klient.vehicles.index')->with('status', 'Pojazd został dodany.');
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
            'numer_rejestracyjny' => ['required', 'string', 'max:15'],
            'vin' => ['nullable', 'string', 'size:17'],
        ]);

        $vehicle->update($data);

        return redirect()->route('klient.vehicles.index')->with('status', 'Dane pojazdu zaktualizowane.');
    }

    public function destroy(Pojazd $vehicle)
    {
        $this->authorizeOwnership($vehicle);
        $vehicle->delete();
        return redirect()->route('klient.vehicles.index')->with('status', 'Pojazd został usunięty.');
    }

    private function authorizeOwnership(Pojazd $vehicle): void
    {
        if ($vehicle->id_uzytkownika !== auth()->id()) {
            abort(403, 'Brak dostępu do tego pojazdu.');
        }
    }
    public function show(Pojazd $vehicle)
    {
        $this->authorizeOwnership($vehicle);

        $rezerwacje = $vehicle->rezerwacje()
            ->with('usluga')
            ->orderByDesc('termin_rozpoczecia')
            ->get();

        return view('klient.vehicles.show', ['pojazd' => $vehicle, 'rezerwacje' => $rezerwacje]);
    }
}