<?php
// app/Http/Controllers/Admin/StanowiskoController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stanowisko;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StanowiskoController extends Controller
{
    public function index()
    {
        $stanowiska = Stanowisko::withCount(['rezerwacje as przyszle_rezerwacje' => function ($q) {
                $q->where('termin_rozpoczecia', '>=', now())
                  ->whereNotIn('status', ['odrzucona', 'anulowana', 'zakończona']);
            }])
            ->orderBy('typ')
            ->orderBy('nazwa')
            ->get();

        return view('admin.workbays.index', compact('stanowiska'));
    }

    public function create()
    {
        return view('admin.workbays.create');
    }

    public function store(Request $request)
    {
        Stanowisko::create($this->waliduj($request));

        return redirect()->route('admin.workbays.index')->with('status', 'Stanowisko zostało dodane.');
    }

    public function edit(Stanowisko $workbay)
    {
        return view('admin.workbays.edit', ['stanowisko' => $workbay]);
    }

    public function update(Request $request, Stanowisko $workbay)
    {
        $dane = $this->waliduj($request, $workbay);

        $zmianaWplywaNaRezerwacje = (!$dane['czy_aktywne'] && $workbay->czy_aktywne)
            || $dane['typ'] !== $workbay->typ;

        $workbay->update($dane);

        $komunikat = 'Stanowisko zostało zaktualizowane.';

        if ($zmianaWplywaNaRezerwacje) {
            $przyszle = $this->liczbaPrzyszlychRezerwacji($workbay);
            if ($przyszle > 0) {
                $komunikat .= " Uwaga: do stanowiska przypisano {$przyszle} przyszłych rezerwacji – sprawdź je i w razie potrzeby zmień ich termin.";
            }
        }

        return redirect()->route('admin.workbays.index')->with('status', $komunikat);
    }

    public function destroy(Stanowisko $workbay)
    {
        if ($workbay->rezerwacje()->exists()) {
            return back()->withErrors([
                'error' => 'To stanowisko ma przypisane rezerwacje. Zamiast usuwać, oznacz je jako nieaktywne.',
            ]);
        }

        $workbay->delete();

        return redirect()->route('admin.workbays.index')->with('status', 'Stanowisko zostało usunięte.');
    }

    private function waliduj(Request $request, ?Stanowisko $stanowisko = null): array
    {
        $dane = $request->validate([
            'nazwa' => [
                'required', 'string', 'max:50',
                Rule::unique('stanowiska', 'nazwa')->ignore($stanowisko?->id_stanowiska, 'id_stanowiska'),
            ],
            'typ' => ['required', Rule::in(array_keys(Stanowisko::TYPY))],
        ]);

        // Niezaznaczony checkbox nie jest wysyłany w formularzu
        $dane['czy_aktywne'] = $request->boolean('czy_aktywne');

        return $dane;
    }

    private function liczbaPrzyszlychRezerwacji(Stanowisko $stanowisko): int
    {
        return $stanowisko->rezerwacje()
            ->where('termin_rozpoczecia', '>=', now())
            ->whereNotIn('status', ['odrzucona', 'anulowana', 'zakończona'])
            ->count();
    }
}