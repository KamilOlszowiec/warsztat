<?php

// app/Http/Controllers/Admin/UslugaController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usluga;
use Illuminate\Http\Request;

class UslugaController extends Controller
{
    public function index()
    {
        $uslugi = Usluga::orderBy('nazwa')->paginate(15);
        return view('admin.services.index', compact('uslugi'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nazwa' => ['required', 'string', 'max:100'],
            'opis' => ['nullable', 'string'],
            'cena' => ['required', 'numeric', 'min:0'],
            'czas_trwania' => ['required', 'integer', 'min:30', function ($attribute, $value, $fail) {
                if ($value % 30 !== 0) {
                    $fail('Czas trwania musi być wielokrotnością 30 minut.');
                }
            }],
            'czy_aktywna' => ['nullable', 'boolean'],
            'maks_jednoczesnie' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $data['czy_aktywna'] = $request->boolean('czy_aktywna');

        Usluga::create($data);

        return redirect()->route('admin.services.index')->with('status', 'Usługa została dodana.');
    }

    public function edit(Usluga $service)
    {
        return view('admin.services.edit', ['usluga' => $service]);
    }

    public function update(Request $request, Usluga $service)
    {
        $data = $request->validate([
            'nazwa' => ['required', 'string', 'max:100'],
            'opis' => ['nullable', 'string'],
            'cena' => ['required', 'numeric', 'min:0'],
            'czas_trwania' => ['required', 'integer', 'min:30', function ($attribute, $value, $fail) {
                if ($value % 30 !== 0) {
                    $fail('Czas trwania musi być wielokrotnością 30 minut.');
                }
            }],
            'czy_aktywna' => ['nullable', 'boolean'],
            'maks_jednoczesnie' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $data['czy_aktywna'] = $request->boolean('czy_aktywna');

        $service->update($data);

        return redirect()->route('admin.services.index')->with('status', 'Usługa została zaktualizowana.');
    }

    public function destroy(Usluga $service)
    {
        $service->delete();
        return redirect()->route('admin.services.index')->with('status', 'Usługa została usunięta.');
    }
}
