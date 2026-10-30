<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GrafikPracownika;
use App\Models\Specjalizacja;
use App\Models\Usluga;
use App\Models\Uzytkownik;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as ResetHasla;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private const FILTRY = ['pracownik', 'klient', 'administrator'];

    public function index(Request $request)
    {
        $filtr = in_array($request->get('rola'), self::FILTRY, true) ? $request->get('rola') : 'wszyscy';
        $q = trim((string) $request->get('q', ''));

        $users = Uzytkownik::query()
            ->when($filtr !== 'wszyscy', fn ($query) => $query->where('rola', $filtr))
            ->when($q !== '', function ($query) use ($q) {
                $wzor = '%' . $q . '%';
                $query->where(function ($w) use ($wzor) {
                    $w->where('imie', 'like', $wzor)
                      ->orWhere('nazwisko', 'like', $wzor)
                      ->orWhereRaw("CONCAT(imie, ' ', nazwisko) LIKE ?", [$wzor])
                      ->orWhere('email', 'like', $wzor);
                });
            })
            ->with('specjalizacje')
            ->withCount(['pojazdy', 'rezerwacjeKlienta'])
            ->orderByRaw("FIELD(rola, 'administrator', 'pracownik', 'klient')")
            ->orderBy('nazwisko')
            ->orderBy('imie')
            ->paginate(20)
            ->withQueryString();

        $liczby = Uzytkownik::selectRaw('rola, COUNT(*) as liczba')->groupBy('rola')->pluck('liczba', 'rola');

        return view('admin.users.index', compact('users', 'filtr', 'q', 'liczby'));
    }

    public function create()
    {
        return view('admin.users.create', $this->daneFormularza());
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->reguly() + [
            'email'    => ['required', 'email', 'max:100', 'unique:uzytkownicy,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Uzytkownik::create([
            'imie'              => $data['imie'],
            'nazwisko'          => $data['nazwisko'],
            'email'             => $data['email'],
            'telefon'           => $data['telefon'] ?? null,
            'rola'              => $data['rola'],
            'password'          => Hash::make($data['password']),
            'email_verified_at' => now(), // konta tworzone przez admina od razu aktywne
        ]);

        $user->specjalizacje()->sync($user->rola === 'pracownik' ? ($data['specjalizacje'] ?? []) : []);

        return redirect()->route('admin.users.edit', $user)
            ->with('status', "Konto {$user->imie} {$user->nazwisko} zostało utworzone. Przekaż hasło startowe i poproś o zmianę po pierwszym logowaniu.");
    }

    /**
     * Karta klienta (tylko do podglądu – dane zmienia klient w swoim panelu).
     */
    public function show(Uzytkownik $user)
    {
        if ($user->rola !== 'klient') {
            return redirect()->route('admin.users.edit', $user);
        }

        $user->load([
            'pojazdy' => fn ($p) => $p->orderBy('marka')->orderBy('model'),
            'pojazdy.rezerwacje' => fn ($r) => $r->with(['uslugi', 'mechanik'])->orderByDesc('termin_rozpoczecia'),
        ]);

        $wszystkie = $user->pojazdy->flatMap->rezerwacje;

        return view('admin.users.show', [
            'user'          => $user,
            'liczbaWizyt'   => $wszystkie->count(),
            'zaplanowane'   => $wszystkie->filter(fn ($r) => in_array($r->status, ['oczekująca', 'zatwierdzona']) && $r->termin_rozpoczecia->isFuture())->count(),
            'anulowane'     => $wszystkie->where('status', 'anulowana')->count(),
            'wydal'         => $wszystkie->where('status', 'zakończona')->sum(fn ($r) => $r->laczna_cena),
            'najblizsza'    => $wszystkie
                ->filter(fn ($r) => in_array($r->status, ['oczekująca', 'zatwierdzona']) && $r->termin_rozpoczecia->isFuture())
                ->sortBy('termin_rozpoczecia')
                ->first(),
            'moznaUsunac'   => $wszystkie->isEmpty(),
        ]);
    }

    public function edit(Uzytkownik $user)
    {
        if ($user->rola === 'klient') {
            return redirect()->route('admin.users.show', $user);
        }

        $user->load('specjalizacje');

        return view('admin.users.edit', $this->daneFormularza() + [
            'user'        => $user,
            'statystyki'  => $user->rola === 'pracownik' ? $this->statystykiPracownika($user) : null,
            'moznaUsunac' => $this->moznaUsunac($user),
        ]);
    }

    public function update(Request $request, Uzytkownik $user)
    {
        if ($user->rola === 'klient') {
            return redirect()->route('admin.users.show', $user)
                ->withErrors(['error' => 'Dane klienta zmienia klient w swoim panelu.']);
        }

        $data = $request->validate($this->reguly() + [
            'email' => ['required', 'email', 'max:100', Rule::unique('uzytkownicy', 'email')->ignore($user->id_uzytkownika, 'id_uzytkownika')],
        ]);

        if (auth()->id() === $user->id_uzytkownika && $data['rola'] !== 'administrator') {
            return back()->withErrors(['rola' => 'Nie możesz odebrać samemu sobie uprawnień administratora.'])->withInput();
        }

        $user->update([
            'imie'     => $data['imie'],
            'nazwisko' => $data['nazwisko'],
            'email'    => $data['email'],
            'telefon'  => $data['telefon'] ?? null,
            'rola'     => $data['rola'],
        ]);

        // Specjalizacje mają znaczenie tylko dla mechaników
        $user->specjalizacje()->sync($user->rola === 'pracownik' ? ($data['specjalizacje'] ?? []) : []);

        return redirect()->route('admin.users.edit', $user)->with('status', 'Zmiany zostały zapisane.');
    }

    public function destroy(Uzytkownik $user)
    {
        if (auth()->id() === $user->id_uzytkownika) {
            return back()->withErrors(['error' => 'Nie możesz usunąć własnego konta.']);
        }

        if (!$this->moznaUsunac($user)) {
            $komunikat = $user->rola === 'klient'
                ? 'Nie można usunąć klienta z historią wizyt – historia napraw musi zostać w systemie.'
                : 'Nie można usunąć pracownika, który obsługiwał lub wykonywał wizyty.';

            return back()->withErrors(['error' => $komunikat]);
        }

        $nazwa = $user->imie . ' ' . $user->nazwisko;
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', "Konto {$nazwa} zostało usunięte.");
    }

    /**
     * Mail z linkiem do ustawienia nowego hasła – admin nie zna ani nie ustawia haseł.
     */
    public function wyslijLinkHasla(Uzytkownik $user)
    {
        $status = ResetHasla::sendResetLink(['email' => $user->email]);

        if ($status === ResetHasla::RESET_THROTTLED) {
            return back()->withErrors(['error' => 'Link został wysłany przed chwilą – odczekaj minutę i spróbuj ponownie.']);
        }

        if ($status !== ResetHasla::RESET_LINK_SENT) {
            return back()->withErrors(['error' => 'Nie udało się wysłać linku do zmiany hasła.']);
        }

        return back()->with('status', "Wysłano link do zmiany hasła na adres {$user->email}.");
    }
     /**
     * Awaryjne ustawienie hasła tymczasowego (np. gdy mail z linkiem nie dochodzi).
     */
    public function ustawHaslo(Request $request, Uzytkownik $user)
    {
        $data = $request->validate([
            'nowe_haslo' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'nowe_haslo' => 'nowe hasło',
        ]);

        $user->update(['password' => Hash::make($data['nowe_haslo'])]);

        return back()->with('status', "Ustawiono hasło tymczasowe dla {$user->imie} {$user->nazwisko}. Przekaż je i poproś o zmianę po zalogowaniu.");
    }   

    public function wyslijWeryfikacje(Uzytkownik $user)
    {
        if ($user->hasVerifiedEmail()) {
            return back()->withErrors(['error' => 'Ten adres e-mail jest już zweryfikowany.']);
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', "Wysłano ponownie link weryfikacyjny na adres {$user->email}.");
    }

    // ---------- Pomocnicze ----------

    private function reguly(): array
    {
        return [
            'imie'            => ['required', 'string', 'max:50'],
            'nazwisko'        => ['required', 'string', 'max:50'],
            'telefon'         => ['nullable', 'string', 'max:15'],
            'rola'            => ['required', Rule::in(['pracownik', 'administrator'])],
            'specjalizacje'   => ['nullable', 'array'],
            'specjalizacje.*' => ['integer', 'exists:specjalizacje,id_specjalizacji'],
        ];
    }

    private function daneFormularza(): array
    {
        return [
            'specjalizacje' => Specjalizacja::orderBy('nazwa')->get(),
            'uslugi'        => Usluga::aktywne()->orderBy('nazwa')->get(),
        ];
    }

    private function moznaUsunac(Uzytkownik $user): bool
    {
        if ($user->rola === 'klient') {
            return !$user->rezerwacjeKlienta()->exists();
        }

        return !$user->wizytyMechanika()->exists();
    }

    private function statystykiPracownika(Uzytkownik $user): array
    {
        $od = today()->startOfWeek(Carbon::MONDAY);
        $do = $od->copy()->addDays(6)->endOfDay();
        $minuty = fn (Carbon $a, Carbon $b) => (int) abs(($b->getTimestamp() - $a->getTimestamp()) / 60);

        $zmianyMin = $user->zmiany()
            ->whereBetween('data', [$od->toDateString(), $do->toDateString()])
            ->get()
            ->sum(fn ($z) => $minuty($z->poczatek(), $z->koniec()));

        $wizytyMin = $user->wizytyMechanika()
            ->whereBetween('termin_rozpoczecia', [$od, $do])
            ->whereNotIn('status', ['odrzucona', 'anulowana'])
            ->get(['termin_rozpoczecia', 'termin_zakonczenia'])
            ->sum(fn ($r) => $minuty($r->termin_rozpoczecia, $r->termin_zakonczenia));

        return [
            'zmianyGodz'  => round($zmianyMin / 60, 1),
            'wizytyGodz'  => round($wizytyMin / 60, 1),
            'procent'     => $zmianyMin > 0 ? (int) round($wizytyMin / $zmianyMin * 100) : null,
            'najblizsza'  => $user->zmiany()->where('data', '>=', today()->toDateString())->orderBy('data')->first(),
        ];
    }
}