@php
    $rola = auth()->user()->rola;
    $layout = [
        'klient'        => 'layouts.klient',
        'pracownik'     => 'layouts.pracownik',
        'administrator' => 'layouts.admin',
    ][$rola] ?? 'layouts.klient';
@endphp
@extends($layout)
@section('title', 'Mój profil')

@section('content')
@php
    $inicjaly = mb_strtoupper(mb_substr($user->imie, 0, 1) . mb_substr($user->nazwisko, 0, 1), 'UTF-8');
    $nazwyRol = ['klient' => 'Klient', 'pracownik' => 'Mechanik', 'administrator' => 'Administrator'];
    $tylkoPodglad = $user->czyPracownik();

    // Statystyki do karty – zależne od roli
    if ($user->czyKlient()) {
        $liczbaPojazdow = $user->pojazdy()->count();
        $liczbaWizyt = \App\Models\Rezerwacja::where('id_klienta', $user->id_uzytkownika)
            ->where('status', 'zakończona')
            ->count();
        $najblizszaWizyta = \App\Models\Rezerwacja::where('id_klienta', $user->id_uzytkownika)
            ->whereIn('status', ['oczekująca', 'zatwierdzona'])
            ->where('termin_rozpoczecia', '>', now())
            ->orderBy('termin_rozpoczecia')
            ->first();
    } elseif ($user->czyPracownik()) {
        $specjalizacje = \App\Models\Specjalizacja::whereHas('pracownicy', fn ($q) => $q->where('id_uzytkownika', $user->id_uzytkownika))
            ->orderBy('nazwa')
            ->pluck('nazwa');
        $najblizszaZmiana = \App\Models\GrafikPracownika::where('id_pracownika', $user->id_uzytkownika)
            ->where('data', '>=', today()->toDateString())
            ->orderBy('data')
            ->first();
        $wizytyDzis = \App\Models\Rezerwacja::where('id_mechanika', $user->id_uzytkownika)
            ->whereDate('termin_rozpoczecia', today())
            ->whereIn('status', ['oczekująca', 'zatwierdzona'])
            ->count();
    }

    $klasaPola = 'w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600';
@endphp

<p class="text-sm font-mono text-orange-500 mb-1">USTAWIENIA</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Mój profil</h1>

<div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6 items-start max-w-4xl">

    {{-- Karta użytkownika --}}
    <aside class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
        <div class="p-6 text-center border-b border-zinc-800">
            <div class="w-20 h-20 mx-auto mb-3 flex items-center justify-center bg-orange-600 text-white text-2xl font-bold" style="border-radius: 50%;">
                {{ $inicjaly }}
            </div>
            <p class="text-lg font-semibold text-zinc-50">{{ $user->imie }} {{ $user->nazwisko }}</p>
            <span class="inline-block mt-1 text-[10px] font-mono px-2 py-0.5 bg-zinc-800 text-zinc-400" style="border-radius: 2px;">
                {{ mb_strtoupper($nazwyRol[$user->rola] ?? $user->rola, 'UTF-8') }}
            </span>
        </div>

        @if (!$tylkoPodglad)
            <div class="p-5 space-y-3 text-sm border-b border-zinc-800">
                <div>
                    <p class="text-xs font-mono text-zinc-500 mb-0.5">E-MAIL</p>
                    <p class="text-zinc-200 break-all">{{ $user->email }}</p>
                    @if ($user->email_verified_at)
                        <span class="inline-block mt-1 text-[10px] font-mono px-1.5 py-0.5 bg-green-900/30 text-green-400" style="border-radius: 2px;">ZWERYFIKOWANY</span>
                    @else
                        <span class="inline-block mt-1 text-[10px] font-mono px-1.5 py-0.5 bg-yellow-900/30 text-yellow-400" style="border-radius: 2px;">NIEZWERYFIKOWANY</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-mono text-zinc-500 mb-0.5">TELEFON</p>
                    <p class="text-zinc-200">{{ $user->telefon ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-mono text-zinc-500 mb-0.5">KONTO OD</p>
                    <p class="text-zinc-200">{{ $user->created_at ? $user->created_at->format('d.m.Y') : '—' }}</p>
                </div>
            </div>
        @endif

        @if ($user->czyKlient())
            <div class="grid grid-cols-2 divide-x divide-zinc-800 border-b border-zinc-800">
                <a href="{{ route('klient.vehicles.index') }}" class="p-4 text-center hover:bg-white/[0.02]">
                    <p class="text-xl font-bold text-zinc-50">{{ $liczbaPojazdow }}</p>
                    <p class="text-[10px] font-mono text-zinc-500">POJAZDY</p>
                </a>
                <a href="{{ route('klient.reservations.index') }}" class="p-4 text-center hover:bg-white/[0.02]">
                    <p class="text-xl font-bold text-zinc-50">{{ $liczbaWizyt }}</p>
                    <p class="text-[10px] font-mono text-zinc-500">WIZYTY</p>
                </a>
            </div>
            <div class="p-5 text-sm">
                <p class="text-xs font-mono text-zinc-500 mb-0.5">NAJBLIŻSZA WIZYTA</p>
                @if ($najblizszaWizyta)
                    <p class="text-orange-400 font-medium">{{ $najblizszaWizyta->termin_rozpoczecia->format('d.m.Y H:i') }}</p>
                @else
                    <a href="{{ route('klient.reservations.create') }}" class="text-orange-500 hover:text-orange-400">Umów wizytę →</a>
                @endif
            </div>
        @elseif ($user->czyPracownik())
            <div class="p-5 space-y-3 text-sm">
                <div>
                    <p class="text-xs font-mono text-zinc-500 mb-1">SPECJALIZACJE</p>
                    @forelse ($specjalizacje as $nazwa)
                        <span class="inline-block mb-1 mr-1 text-xs px-2 py-0.5 border border-zinc-700 text-zinc-300" style="border-radius: 2px;">{{ $nazwa }}</span>
                    @empty
                        <p class="text-zinc-500">Brak przypisanych specjalizacji.</p>
                    @endforelse
                </div>
                <div>
                    <p class="text-xs font-mono text-zinc-500 mb-0.5">NAJBLIŻSZA ZMIANA</p>
                    @if ($najblizszaZmiana)
                        <p class="text-zinc-200">
                            {{ $najblizszaZmiana->data->isToday() ? 'dziś' : $najblizszaZmiana->data->format('d.m.Y') }},
                            {{ substr($najblizszaZmiana->godzina_od, 0, 5) }}–{{ substr($najblizszaZmiana->godzina_do, 0, 5) }}
                        </p>
                    @else
                        <p class="text-zinc-500">Brak zaplanowanych zmian.</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-mono text-zinc-500 mb-0.5">MOJE WIZYTY DZIŚ</p>
                    <p class="text-zinc-200">{{ $wizytyDzis }}</p>
                </div>
            </div>
        @endif
    </aside>

    {{-- Zakładki --}}
    <div>
        @error('error')
            <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
                {{ $message }}
            </div>
        @enderror

        <div class="flex border-b border-zinc-800 mb-5" role="tablist">
            <button type="button" data-zakladka="dane" role="tab"
                class="zakladka px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition">
                {{ $tylkoPodglad ? 'Dane konta' : 'Dane osobowe' }}
            </button>
            <button type="button" data-zakladka="haslo" role="tab"
                class="zakladka px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition">
                Hasło
            </button>
        </div>

        @if ($tylkoPodglad)
            {{-- Dane konta służbowego – tylko do podglądu --}}
            <div data-panel="dane" class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
                <div class="flex gap-3 items-start px-5 py-3.5 bg-[#18191D] border-b border-zinc-800">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 shrink-0 mt-0.5">
                        <rect x="5" y="11" width="14" height="10" rx="1"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                    </svg>
                    <p class="text-sm text-zinc-300">To konto służbowe – dane zmienia administrator warsztatu. Jeśli coś się nie zgadza, zgłoś to przełożonemu.</p>
                </div>
                <dl class="divide-y divide-zinc-800">
                    <div class="flex flex-wrap gap-x-4 gap-y-1 px-5 py-3.5">
                        <dt class="w-36 shrink-0 text-[11px] font-mono text-zinc-400 pt-0.5">IMIĘ I NAZWISKO</dt>
                        <dd class="text-sm text-zinc-100">{{ $user->imie }} {{ $user->nazwisko }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 px-5 py-3.5">
                        <dt class="w-36 shrink-0 text-[11px] font-mono text-zinc-400 pt-0.5">E-MAIL (LOGIN)</dt>
                        <dd class="text-sm text-zinc-100">
                            {{ $user->email }}
                            @if ($user->email_verified_at)
                                <span class="ml-1.5 text-[10px] font-mono px-1.5 py-0.5 bg-green-900/30 text-green-400" style="border-radius: 2px;">ZWERYFIKOWANY</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 px-5 py-3.5">
                        <dt class="w-36 shrink-0 text-[11px] font-mono text-zinc-400 pt-0.5">TELEFON</dt>
                        <dd class="text-sm text-zinc-100">{{ $user->telefon ?: '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 px-5 py-3.5">
                        <dt class="w-36 shrink-0 text-[11px] font-mono text-zinc-400 pt-0.5">KONTO OD</dt>
                        <dd class="text-sm text-zinc-100">{{ $user->created_at ? $user->created_at->format('d.m.Y') : '—' }}</dd>
                    </div>
                </dl>
            </div>
        @else
            {{-- Dane osobowe – formularz --}}
            <form data-panel="dane" method="POST" action="{{ route('profile.update') }}#dane"
                  class="bg-[#1F2128] border border-zinc-800 p-6 space-y-4" style="border-radius: 2px;">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-zinc-400 mb-1.5">IMIĘ</label>
                        <input type="text" name="imie" value="{{ old('imie', $user->imie) }}" class="{{ $klasaPola }}" style="border-radius: 2px;">
                        @error('imie') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-zinc-400 mb-1.5">NAZWISKO</label>
                        <input type="text" name="nazwisko" value="{{ old('nazwisko', $user->nazwisko) }}" class="{{ $klasaPola }}" style="border-radius: 2px;">
                        @error('nazwisko') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-zinc-400 mb-1.5">E-MAIL</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $klasaPola }}" style="border-radius: 2px;">
                        @error('email') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-zinc-400 mb-1.5">TELEFON</label>
                        <input type="text" name="telefon" value="{{ old('telefon', $user->telefon) }}" placeholder="np. 500 100 200" class="{{ $klasaPola }}" style="border-radius: 2px;">
                        @error('telefon') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <p class="text-xs text-zinc-500">Zmiana adresu e-mail wymaga jego ponownej weryfikacji – wyślemy link na nowy adres.</p>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition" style="border-radius: 2px;">
                        Zapisz zmiany
                    </button>
                </div>
            </form>
        @endif

        {{-- Hasło --}}
        <form data-panel="haslo" method="POST" action="{{ route('profile.password') }}#haslo"
              class="hidden bg-[#1F2128] border border-zinc-800 p-6 space-y-4" style="border-radius: 2px;">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">OBECNE HASŁO</label>
                <input type="password" name="current_password" autocomplete="current-password" class="{{ $klasaPola }}" style="border-radius: 2px;">
                @error('current_password') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-mono text-zinc-400 mb-1.5">NOWE HASŁO</label>
                    <input type="password" name="password" autocomplete="new-password" class="{{ $klasaPola }}" style="border-radius: 2px;">
                    @error('password') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-mono text-zinc-400 mb-1.5">POWTÓRZ NOWE HASŁO</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" class="{{ $klasaPola }}" style="border-radius: 2px;">
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition" style="border-radius: 2px;">
                    Zmień hasło
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const zakladki = document.querySelectorAll('.zakladka');
    const panele = document.querySelectorAll('[data-panel]');
    const bledy = @json($errors->keys());

    function pokaz(nazwa) {
        zakladki.forEach(z => {
            const aktywna = z.dataset.zakladka === nazwa;
            z.classList.toggle('border-orange-600', aktywna);
            z.classList.toggle('text-zinc-50', aktywna);
            z.classList.toggle('border-transparent', !aktywna);
            z.classList.toggle('text-zinc-500', !aktywna);
            z.setAttribute('aria-selected', aktywna ? 'true' : 'false');
        });
        panele.forEach(p => p.classList.toggle('hidden', p.dataset.panel !== nazwa));
        history.replaceState(null, '', '#' + nazwa);
    }

    zakladki.forEach(z => z.addEventListener('click', () => pokaz(z.dataset.zakladka)));

    // Kolejność: zakładka z błędem walidacji, potem końcówka adresu (#haslo), domyślnie dane
    let start = 'dane';
    if (bledy.some(k => ['current_password', 'password'].includes(k))) {
        start = 'haslo';
    } else if (bledy.length) {
        start = 'dane';
    } else if (location.hash === '#haslo') {
        start = 'haslo';
    }

    pokaz(start);
})();
</script>
@endsection