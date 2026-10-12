<?php
// app/Http/Controllers/Admin/SpecjalizacjaController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specjalizacja;
use App\Models\Uzytkownik;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecjalizacjaController extends Controller
{
    public function index()
    {
        $specjalizacje = Specjalizacja::with(['pracownicy' => fn ($q) => $q->orderBy('nazwisko')])
            ->withCount('uslugi')
            ->orderBy('nazwa')
            ->get();

        return view('admin.specializations.index', compact('specjalizacje'));
    }

    public function create()
    {
        return view('admin.specializations.create', ['pracownicy' => $this->pracownicy()]);
    }

    public function store(Request $request)
    {
        $dane = $this->waliduj($request);

        $specjalizacja = Specjalizacja::create(['nazwa' => $dane['nazwa']]);
        $specjalizacja->pracownicy()->sync($dane['pracownicy'] ?? []);

        return redirect()->route('admin.specializations.index')->with('status', 'Specjalizacja została dodana.');
    }

    public function edit(Specjalizacja $specjalizacja)
    {
        $specjalizacja->load('pracownicy');

        return view('admin.specializations.edit', [
            'specjalizacja' => $specjalizacja,
            'pracownicy'    => $this->pracownicy(),
        ]);
    }

    public function update(Request $request, Specjalizacja $specjalizacja)
    {
        $dane = $this->waliduj($request, $specjalizacja);

        $specjalizacja->update(['nazwa' => $dane['nazwa']]);
        $specjalizacja->pracownicy()->sync($dane['pracownicy'] ?? []);

        return redirect()->route('admin.specializations.index')->with('status', 'Specjalizacja została zaktualizowana.');
    }

    public function destroy(Specjalizacja $specjalizacja)
    {
        $liczbaUslug = $specjalizacja->uslugi()->count();

        if ($liczbaUslug > 0) {
            return back()->withErrors([
                'error' => "Specjalizacji „{$specjalizacja->nazwa}” wymaga {$liczbaUslug} usług. Najpierw zmień w nich wymaganą specjalizację.",
            ]);
        }

        // Powiązania z pracownikami usuwa kaskada w tabeli pracownik_specjalizacja
        $specjalizacja->delete();

        return redirect()->route('admin.specializations.index')->with('status', 'Specjalizacja została usunięta.');
    }

    private function waliduj(Request $request, ?Specjalizacja $specjalizacja = null): array
    {
        return $request->validate([
            'nazwa' => [
                'required', 'string', 'max:50',
                Rule::unique('specjalizacje', 'nazwa')->ignore($specjalizacja?->id_specjalizacji, 'id_specjalizacji'),
            ],
            'pracownicy'   => ['nullable', 'array'],
            'pracownicy.*' => ['integer', Rule::exists('uzytkownicy', 'id_uzytkownika')->where('rola', 'pracownik')],
        ], [
            'nazwa.unique' => 'Specjalizacja o tej nazwie już istnieje.',
        ]);
    }

    private function pracownicy()
    {
        return Uzytkownik::where('rola', 'pracownik')->orderBy('nazwisko')->orderBy('imie')->get();
    }
}