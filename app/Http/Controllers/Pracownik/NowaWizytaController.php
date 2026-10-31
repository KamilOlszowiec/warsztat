<?php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Pojazd;
use App\Models\Usluga;
use App\Models\Uzytkownik;
use App\Notifications\RezerwacjaZatwierdzona;
use App\Services\DostepnoscRezerwacjiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Rezerwacja przyjmowana przez pracownika lub administratora,
 * np. podczas rozmowy telefonicznej z klientem.
 * Kolejność: usługi → termin → klient → pojazd → zapis.
 */
class NowaWizytaController extends Controller
{
    public function __construct(private DostepnoscRezerwacjiService $dostepnosc)
    {
    }

    public function create()
    {
        // Po błędzie walidacji przywracamy wybranego wcześniej klienta razem z jego pojazdami
        $wybranyKlient = null;
        if (old('klient_tryb') === 'istniejacy' && old('id_klienta')) {
            $k = Uzytkownik::where('rola', 'klient')->with('pojazdy')->find(old('id_klienta'));
            if ($k) {
                $wybranyKlient = $this->klientJson($k);
            }
        }

        return view('pracownik.reservations.nowa', [
            'uslugi'        => Usluga::aktywne()->orderBy('nazwa')->get(),
            'czyAdmin'      => auth()->user()->czyAdministrator(),
            'wybranyKlient' => $wybranyKlient,
        ] + $this->dostepnosc->daneKalendarza());
    }

    /**
     * Endpoint JSON: mechanicy zdolni wykonać wybrane usługi (pole wyboru administratora).
     */
    public function mechanicy(Request $request)
    {
        $uslugi = $this->uslugiZZapytania($request);

        return response()->json([
            'mechanicy' => $this->dostepnosc->listaMechanikow($uslugi),
        ]);
    }

    /**
     * Endpoint JSON: wolne godziny. Pracownik widzi tylko swoje terminy.
     */
    public function terminy(Request $request)
    {
        $uslugi = $this->uslugiZZapytania($request);

        $dane = $request->validate([
            'data'     => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->dostepnosc->maksData()],
            'mechanik' => ['nullable', 'integer'],
        ]);

        if (!$this->dostepnosc->czyKtosMozeWykonac($uslugi)) {
            return response()->json([
                'godziny'   => [],
                'komunikat' => 'Żaden mechanik nie wykona wszystkich wybranych usług podczas jednej wizyty.',
            ]);
        }

        $godziny = $this->dostepnosc->dostepneGodziny(
            $uslugi, $dane['data'], null, null, $this->mechanikDlaTerminu($dane['mechanik'] ?? null)
        );

        return response()->json([
            'godziny'   => $godziny,
            'komunikat' => empty($godziny) ? 'Brak wolnych terminów w wybranym dniu.' : null,
        ]);
    }

    /**
     * Endpoint JSON: wyszukiwanie klientów po nazwisku, telefonie, e-mailu lub numerze rejestracyjnym.
     */
    public function klienci(Request $request)
    {
        $fraza = trim((string) $request->get('q'));

        if (mb_strlen($fraza) < 2) {
            return response()->json(['klienci' => []]);
        }

        $klienci = Uzytkownik::where('rola', 'klient')
            ->where(function ($q) use ($fraza) {
                $wzor = '%' . $fraza . '%';
                $q->where('nazwisko', 'like', $wzor)
                  ->orWhere('imie', 'like', $wzor)
                  ->orWhere('telefon', 'like', '%' . str_replace(' ', '', $fraza) . '%')
                  ->orWhere('email', 'like', $wzor)
                  ->orWhereHas('pojazdy', fn ($p) => $p->where('numer_rejestracyjny', 'like', $wzor));
            })
            ->with(['pojazdy' => fn ($q) => $q->orderBy('marka')])
            ->orderBy('nazwisko')
            ->limit(10)
            ->get();

        return response()->json([
            'klienci' => $klienci->map(fn ($k) => $this->klientJson($k))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $dane = $request->validate([
            'uslugi'       => ['required', 'array', 'min:1'],
            'uslugi.*'     => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
            'data'         => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->dostepnosc->maksData()],
            'godzina'      => ['required', 'date_format:H:i'],
            'id_mechanika' => ['nullable', 'integer'],

            'klient_tryb'  => ['required', Rule::in(['istniejacy', 'nowy'])],
            'id_klienta'   => ['required_if:klient_tryb,istniejacy', 'nullable',
                               Rule::exists('uzytkownicy', 'id_uzytkownika')->where('rola', 'klient')],
            'imie'         => ['required_if:klient_tryb,nowy', 'nullable', 'string', 'max:50'],
            'nazwisko'     => ['required_if:klient_tryb,nowy', 'nullable', 'string', 'max:50'],
            'telefon'      => ['required_if:klient_tryb,nowy', 'nullable', 'string', 'max:15',
                               Rule::unique('uzytkownicy', 'telefon')->where('rola', 'klient')],
            'email'        => ['nullable', 'email', 'max:100', 'unique:uzytkownicy,email'],

            'pojazd_tryb'         => ['required', Rule::in(['istniejacy', 'nowy'])],
            'id_pojazdu'          => ['required_if:pojazd_tryb,istniejacy', 'nullable', 'integer'],
            'marka'               => ['required_if:pojazd_tryb,nowy', 'nullable', 'string', 'max:50'],
            'model'               => ['required_if:pojazd_tryb,nowy', 'nullable', 'string', 'max:50'],
            'numer_rejestracyjny' => ['required_if:pojazd_tryb,nowy', 'nullable', 'string', 'max:15', 'unique:pojazdy,numer_rejestracyjny'],
            'rok_produkcji'       => ['nullable', 'digits:4', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'vin'                 => ['nullable', 'string', 'size:17'],

            'uwagi'        => ['nullable', 'string', 'max:500'],
        ], [
            'uslugi.required'        => 'Wybierz co najmniej jedną usługę.',
            'godzina.required'       => 'Wybierz godzinę wizyty.',
            'id_klienta.required_if' => 'Wybierz klienta z listy albo dodaj nowego.',
            'telefon.unique'         => 'Klient z tym numerem telefonu już istnieje – wyszukaj go na liście.',
            'email.unique'           => 'Ten adres e-mail jest już przypisany do innego konta.',
            'id_pojazdu.required_if' => 'Wybierz pojazd klienta albo dodaj nowy.',
            'numer_rejestracyjny.unique' => 'Pojazd z tym numerem rejestracyjnym jest już w systemie – wyszukaj klienta po numerze.',
        ], [
            'imie' => 'imię', 'telefon' => 'telefon', 'marka' => 'marka', 'model' => 'model',
            'numer_rejestracyjny' => 'numer rejestracyjny', 'rok_produkcji' => 'rok produkcji',
        ]);

        // Nowy klient nie ma jeszcze pojazdów
        if ($dane['klient_tryb'] === 'nowy' && $dane['pojazd_tryb'] !== 'nowy') {
            return back()->withErrors(['id_pojazdu' => 'Dla nowego klienta dodaj nowy pojazd.'])->withInput();
        }

        $uslugi = Usluga::aktywne()->whereIn('id_uslugi', $dane['uslugi'])->get();
        $start = Carbon::parse($dane['data'] . ' ' . $dane['godzina']);
        $mechanik = $this->mechanikDlaTerminu($dane['id_mechanika'] ?? null);

        try {
            // Klient, pojazd i rezerwacja zapisują się razem albo wcale –
            // jeśli termin się zajął, nie zostaje puste konto ani pojazd.
            [$klient, $rezerwacja, $nowyKlient] = DB::transaction(function () use ($dane, $uslugi, $start, $mechanik) {
                $nowyKlient = $dane['klient_tryb'] === 'nowy';

                $klient = $nowyKlient
                    ? Uzytkownik::create([
                        'imie'     => $dane['imie'],
                        'nazwisko' => $dane['nazwisko'],
                        'telefon'  => $dane['telefon'],
                        'email'    => $dane['email'] ?? null,
                        // Losowe hasło – klient ustawi własne linkiem z maila
                        'password' => Hash::make(Str::random(40)),
                        'rola'     => 'klient',
                    ])
                    : Uzytkownik::findOrFail($dane['id_klienta']);

                if ($dane['pojazd_tryb'] === 'nowy') {
                    $pojazd = Pojazd::create([
                        'id_uzytkownika'      => $klient->id_uzytkownika,
                        'marka'               => $dane['marka'],
                        'model'               => $dane['model'],
                        'numer_rejestracyjny' => mb_strtoupper(trim($dane['numer_rejestracyjny']), 'UTF-8'),
                        'rok_produkcji'       => $dane['rok_produkcji'] ?? null,
                        'vin'                 => isset($dane['vin']) ? mb_strtoupper($dane['vin'], 'UTF-8') : null,
                    ]);
                } else {
                    $pojazd = Pojazd::where('id_pojazdu', $dane['id_pojazdu'])
                        ->where('id_uzytkownika', $klient->id_uzytkownika)
                        ->firstOrFail();
                }

                $rezerwacja = $this->dostepnosc->utworzRezerwacje([
                    'id_klienta' => $klient->id_uzytkownika,
                    'id_pojazdu' => $pojazd->id_pojazdu,
                    'uwagi'      => $dane['uwagi'] ?? null,
                    // Termin uzgodniony z klientem – nie wymaga zatwierdzania
                    'status'     => 'zatwierdzona',
                ], $uslugi, $start, $mechanik);

                if (!$rezerwacja) {
                    throw new RuntimeException('termin_zajety');
                }

                return [$klient, $rezerwacja, $nowyKlient];
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'termin_zajety') {
                throw $e;
            }

            return back()
                ->withErrors(['godzina' => 'Ten termin właśnie się zajął. Wybierz inną godzinę – dane klienta zostały zachowane.'])
                ->withInput();
        }

        $this->powiadomKlienta($klient, $rezerwacja, $nowyKlient);

        $komunikat = 'Wizyta została zapisana.';
        if (!$klient->email) {
            $komunikat .= ' Klient nie podał e-maila, więc nie otrzyma potwierdzenia.';
        } elseif ($nowyKlient) {
            $komunikat .= ' Klient otrzyma e-mail z potwierdzeniem i linkiem do ustawienia hasła.';
        } else {
            $komunikat .= ' Klient otrzyma e-mail z potwierdzeniem.';
        }

        return redirect()
            ->route('pracownik.reservations.plan', ['data' => $start->toDateString(), 'wizyta' => $rezerwacja->id_rezerwacji])
            ->with('status', $komunikat);
    }

    /**
     * Potwierdzenie wizyty, a dla nowego klienta z e-mailem także link do ustawienia hasła.
     * Błąd poczty nie cofa zapisanej wizyty.
     */
    private function powiadomKlienta(Uzytkownik $klient, $rezerwacja, bool $nowyKlient): void
    {
        if (!$klient->email) {
            return;
        }

        try {
            $rezerwacja->load(['uslugi', 'pojazd', 'mechanik']);
            $klient->notify(new RezerwacjaZatwierdzona($rezerwacja));

            if ($nowyKlient) {
                Password::sendResetLink(['email' => $klient->email]);
            }
        } catch (\Exception $e) {
            Log::warning('Nie udało się wysłać wiadomości do klienta zapisanego telefonicznie: ' . $e->getMessage());
        }
    }

    /**
     * Pracownik zapisuje wizytę zawsze na siebie, administrator – na wskazanego lub dowolnego mechanika.
     */
    private function mechanikDlaTerminu(?int $wybrany): ?int
    {
        return auth()->user()->czyAdministrator() ? $wybrany : (int) auth()->id();
    }

    private function uslugiZZapytania(Request $request)
    {
        $dane = $request->validate([
            'uslugi'   => ['required', 'array', 'min:1'],
            'uslugi.*' => ['integer', 'distinct', Rule::exists('uslugi', 'id_uslugi')->where('czy_aktywna', true)],
        ]);

        return Usluga::aktywne()->whereIn('id_uslugi', $dane['uslugi'])->get();
    }

    private function klientJson(Uzytkownik $k): array
    {
        return [
            'id'      => (int) $k->id_uzytkownika,
            'nazwa'   => $k->imie . ' ' . $k->nazwisko,
            'telefon' => $k->telefon,
            'email'   => $k->email,
            'pojazdy' => $k->pojazdy->map(fn ($p) => [
                'id'   => (int) $p->id_pojazdu,
                'opis' => $p->marka . ' ' . $p->model . ' (' . $p->numer_rejestracyjny . ')',
            ])->values()->all(),
        ];
    }

}
