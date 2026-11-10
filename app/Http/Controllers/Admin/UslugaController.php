<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usluga;
use App\Models\Uzytkownik;
use Illuminate\Http\Request;

class UslugaController extends Controller
{
    public function index(Request $request)
    {
        $widok = $request->get('widok') === 'ukryte' ? 'ukryte' : 'aktywne';

        $uslugi = Usluga::where('czy_aktywna', $widok === 'aktywne')
            ->with(['specjalizacja.pracownicy' => fn ($q) => $q->where('rola', 'pracownik')->orderBy('nazwisko')])
            ->withCount([
                'rezerwacje',
                'rezerwacje as wizyty_30_dni' => fn ($q) => $q
                    ->where('rezerwacje.created_at', '>=', now()->subDays(30))
                    ->whereNotIn('rezerwacje.status', ['odrzucona', 'anulowana']),
            ])
            ->orderBy('nazwa')
            ->get();

        $liczby = [
            'aktywne' => Usluga::where('czy_aktywna', true)->count(),
            'ukryte'  => Usluga::where('czy_aktywna', false)->count(),
        ];

        // Dla usług bez wymaganej specjalizacji – wszyscy mechanicy
        $wszyscyMechanicy = Uzytkownik::where('rola', 'pracownik')->orderBy('nazwisko')->get();

        return view('admin.services.index', compact('uslugi', 'widok', 'liczby', 'wszyscyMechanicy'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->reguly());
        $data['czy_aktywna'] = $request->boolean('czy_aktywna');

        $usluga = Usluga::create($data);

        return redirect()
            ->route('admin.services.index', $usluga->czy_aktywna ? [] : ['widok' => 'ukryte'])
            ->with('status', "Usługa „{$usluga->nazwa}” została dodana.");
    }

    public function edit(Usluga $service)
    {
        return view('admin.services.edit', [
            'usluga'       => $service,
            'moznaUsunac'  => !$service->rezerwacje()->exists(),
        ]);
    }

    public function update(Request $request, Usluga $service)
    {
        $data = $request->validate($this->reguly());
        $data['czy_aktywna'] = $request->boolean('czy_aktywna');

        $service->update($data);

        return redirect()
            ->route('admin.services.index', $service->czy_aktywna ? [] : ['widok' => 'ukryte'])
            ->with('status', "Usługa „{$service->nazwa}” została zaktualizowana.");
    }

    public function przelaczWidocznosc(Usluga $service)
    {
        $service->update(['czy_aktywna' => !$service->czy_aktywna]);

        $komunikat = $service->czy_aktywna
            ? "Usługa „{$service->nazwa}” wróciła do oferty."
            : "Usługa „{$service->nazwa}” została ukryta – klienci nie zobaczą jej w kreatorze, a historia wizyt zostaje.";

        return back()->with('status', $komunikat);
    }

    public function destroy(Usluga $service)
    {
        if ($service->rezerwacje()->exists()) {
            return back()->withErrors([
                'error' => "Usługi „{$service->nazwa}” nie można usunąć, bo była już rezerwowana. Ukryj ją, żeby zniknęła z oferty.",
            ]);
        }

        $nazwa = $service->nazwa;
        $service->delete();

        return redirect()->route('admin.services.index')->with('status', "Usługa „{$nazwa}” została usunięta.");
    }

    private function reguly(): array
    {
        return [
            'nazwa' => ['required', 'string', 'max:100'],
            'opis' => ['nullable', 'string'],
            'cena' => ['required', 'numeric', 'min:0'],
            'czas_trwania' => ['required', 'integer', 'min:30', 'max:480', function ($attribute, $value, $fail) {
                if ($value % 30 !== 0) {
                    $fail('Czas trwania musi być wielokrotnością 30 minut.');
                }
            }],
            'czy_aktywna' => ['nullable', 'boolean'],
            'id_specjalizacji' => ['required', 'exists:specjalizacje,id_specjalizacji'],
        ];
    }
}