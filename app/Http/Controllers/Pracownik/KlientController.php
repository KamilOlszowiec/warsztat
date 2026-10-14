<?php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use App\Models\Uzytkownik;
use Illuminate\Http\Request;

class KlientController extends Controller
{
    private const LIMIT_WYNIKOW = 50;

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        // Lista klientów pasujących do frazy
        $klienci = Uzytkownik::where('rola', 'klient')
            ->when($q !== '', function ($query) use ($q) {
                $bezSpacji = str_replace(' ', '', $q);
                $wzor = '%' . $q . '%';
                $wzorBezSpacji = '%' . $bezSpacji . '%';

                $query->where(function ($w) use ($wzor, $wzorBezSpacji) {
                    $w->where('imie', 'like', $wzor)
                      ->orWhere('nazwisko', 'like', $wzor)
                      ->orWhereRaw("CONCAT(imie, ' ', nazwisko) LIKE ?", [$wzor])
                      ->orWhere('email', 'like', $wzor)
                      ->orWhereRaw("REPLACE(telefon, ' ', '') LIKE ?", [$wzorBezSpacji])
                      ->orWhereHas('pojazdy', function ($p) use ($wzor, $wzorBezSpacji) {
                          $p->whereRaw("REPLACE(numer_rejestracyjny, ' ', '') LIKE ?", [$wzorBezSpacji])
                            ->orWhere('marka', 'like', $wzor)
                            ->orWhere('model', 'like', $wzor);
                      });
                });
            })
            ->with(['pojazdy' => fn ($p) => $p->orderBy('marka')])
            ->orderBy('nazwisko')
            ->orderBy('imie')
            ->limit(self::LIMIT_WYNIKOW)
            ->get();

        // Liczba wizyt każdego klienta z listy – jedno zapytanie dla wszystkich
        $liczbyWizyt = Rezerwacja::whereIn('id_klienta', $klienci->pluck('id_uzytkownika'))
            ->selectRaw('id_klienta, COUNT(*) as liczba')
            ->groupBy('id_klienta')
            ->pluck('liczba', 'id_klienta');

        // Wybrany klient: z adresu, a jeśli go nie ma na liście – pierwszy wynik
        $idWybranego = (int) $request->get('klient');
        if (!$klienci->contains('id_uzytkownika', $idWybranego)) {
            $idWybranego = $klienci->first()?->id_uzytkownika;
        }

        $wybrany = $idWybranego
            ? Uzytkownik::where('rola', 'klient')
                ->with([
                    'pojazdy' => fn ($p) => $p->orderBy('marka')->orderBy('model'),
                    'pojazdy.rezerwacje' => fn ($r) => $r->with(['uslugi', 'mechanik'])->orderByDesc('termin_rozpoczecia'),
                ])
                ->find($idWybranego)
            : null;

        return view('pracownik.clients.index', [
            'q'           => $q,
            'klienci'     => $klienci,
            'liczbyWizyt' => $liczbyWizyt,
            'wybrany'     => $wybrany,
            'obcieto'     => $klienci->count() >= self::LIMIT_WYNIKOW,
        ]);
    }
}