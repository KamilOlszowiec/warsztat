@extends(auth()->user()->rola === 'administrator' ? 'layouts.admin' : 'layouts.pracownik')
@section('title', 'Nowa wizyta')

@section('content')
@php
    $pole = 'w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600';
    $etykieta = 'block text-xs font-mono text-zinc-400 mb-1.5';
    $kafelek = 'bg-[#1F2128] border border-zinc-800 hover:border-zinc-600 peer-checked:border-orange-600 peer-checked:bg-orange-600/10 transition';
@endphp

<a href="{{ route('pracownik.reservations.plan') }}" class="inline-block text-sm text-zinc-400 hover:text-zinc-200 mb-4">← Plan dnia</a>
<p class="text-sm font-mono text-orange-500 mb-1">NOWA WIZYTA</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-1">Umów wizytę dla klienta</h1>
<p class="text-sm text-zinc-400 mb-6">
    Wizyta umówiona przez telefon lub na miejscu – od razu ma status „zatwierdzona”.
    @unless ($czyAdmin) Zostanie przypisana do Ciebie. @endunless
</p>

{{-- Pasek kroków --}}
<div class="max-w-4xl mb-8">
    <p id="krok-licznik" class="text-xs font-mono text-zinc-500 text-center mb-3">KROK 1 Z 5</p>
    <div class="flex items-center">
        @foreach ([1 => 'Usługi', 2 => 'Termin', 3 => 'Klient', 4 => 'Pojazd', 5 => 'Zapis'] as $nr => $nazwa)
            <button type="button" data-krok-wskaznik="{{ $nr }}" class="flex flex-col items-center gap-1.5 shrink-0 disabled:cursor-not-allowed">
                <span class="krok-kolko w-9 h-9 flex items-center justify-center text-sm font-semibold border bg-[#1F2128] border-zinc-700 text-zinc-500" style="border-radius: 50%;">{{ $nr }}</span>
                <span class="krok-nazwa text-xs text-zinc-500">{{ $nazwa }}</span>
            </button>
            @if ($nr < 5)
                <div data-krok-linia="{{ $nr }}" class="flex-1 h-0.5 mx-2 mb-5 bg-zinc-800"></div>
            @endif
        @endforeach
    </div>
</div>

<form id="formularz" method="POST" action="{{ route('pracownik.reservations.nowa.store') }}"
      class="max-w-4xl grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6 items-start">
    @csrf
    <input type="hidden" name="data" id="pole-data" value="{{ old('data') }}">
    <input type="hidden" name="pojazd_tryb" id="pole-pojazd-tryb" value="{{ old('pojazd_tryb', 'istniejacy') }}">

    <div>
        @if ($errors->any())
            <div class="mb-5 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- KROK 1: Usługi --}}
        <section data-krok="1">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Wybierz usługi</h2>
            <p class="text-sm text-zinc-500 mb-5">Zaznacz wszystko, czego potrzebuje klient – usługi zostaną wykonane podczas jednej wizyty.</p>

            <div class="space-y-1.5">
                @foreach ($uslugi as $u)
                    @php
                        $h = intdiv($u->czas_trwania, 60);
                        $m = $u->czas_trwania % 60;
                        $czasOpis = trim(($h ? $h . ' h ' : '') . ($m ? $m . ' min' : ''));
                    @endphp
                    <label class="block cursor-pointer">
                        <input type="checkbox" name="uslugi[]" value="{{ $u->id_uslugi }}"
                               data-nazwa="{{ $u->nazwa }}" data-czas="{{ $u->czas_trwania }}" data-cena="{{ $u->cena }}"
                               class="peer sr-only" {{ in_array($u->id_uslugi, old('uslugi', [])) ? 'checked' : '' }}>
                        <div class="flex items-center justify-between gap-4 px-4 py-2.5 {{ $kafelek }}" style="border-radius: 2px;">
                            <p class="text-sm font-medium text-zinc-100">
                                {{ $u->nazwa }}
                                <span class="ml-2 text-xs font-mono text-zinc-500 font-normal">{{ $czasOpis }}</span>
                            </p>
                            <p class="text-sm font-semibold text-zinc-100 whitespace-nowrap">{{ number_format($u->cena, 2) }} zł</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- KROK 2: Termin --}}
        <section data-krok="2" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Wybierz termin</h2>
            <p class="text-sm text-zinc-500 mb-5">
                Dni zamknięte i dni wolne warsztatu są wyszarzone.
                @unless ($czyAdmin) Widoczne są tylko godziny, w których masz zmianę i jesteś wolny. @endunless
            </p>

            @if ($czyAdmin)
                <div class="mb-5 max-w-sm">
                    <label for="pole-mechanik" class="{{ $etykieta }}">MECHANIK</label>
                    <select id="pole-mechanik" name="id_mechanika" class="w-full bg-[#1F2128] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                        <option value="">Dowolny mechanik (najwcześniejszy termin)</option>
                    </select>
                    <p class="text-xs text-zinc-500 mt-1.5">Lista zawiera tylko mechaników, którzy wykonują wszystkie wybrane usługi.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-[minmax(0,300px)_1fr] gap-6">
                {{-- Kalendarz --}}
                <div class="bg-[#1F2128] border border-zinc-800 p-4" style="border-radius: 2px;">
                    <div class="flex items-center justify-between mb-3">
                        <button type="button" id="kal-poprzedni" class="w-8 h-8 text-lg text-zinc-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">‹</button>
                        <p id="kal-miesiac" class="text-sm font-medium text-zinc-200 capitalize"></p>
                        <button type="button" id="kal-nastepny" class="w-8 h-8 text-lg text-zinc-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">›</button>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-mono text-zinc-500 mb-1">
                        @foreach (['PN', 'WT', 'ŚR', 'CZ', 'PT', 'SO', 'ND'] as $dzien)
                            <span>{{ $dzien }}</span>
                        @endforeach
                    </div>
                    <div id="kal-dni" class="grid grid-cols-7 gap-1"></div>
                </div>

                {{-- Godziny --}}
                <div>
                    <p class="text-xs font-mono text-zinc-400 mb-1">DOSTĘPNE GODZINY</p>
                    <p id="wybrana-data-opis" class="text-sm text-zinc-300 mb-3"></p>
                    <p id="sloty-komunikat" class="text-zinc-500 text-sm">Wybierz dzień w kalendarzu.</p>
                    <div id="sloty" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
                </div>
            </div>
        </section>

        {{-- KROK 3: Klient --}}
        <section data-krok="3" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Klient</h2>
            <p class="text-sm text-zinc-500 mb-5">Wyszukaj klienta w systemie albo dodaj nowego.</p>

            <div class="flex gap-2 mb-5">
                @foreach (['istniejacy' => 'Klient w systemie', 'nowy' => 'Nowy klient'] as $wartosc => $opis)
                    <label class="cursor-pointer">
                        <input type="radio" name="klient_tryb" value="{{ $wartosc }}" class="peer sr-only"
                               {{ old('klient_tryb', 'istniejacy') === $wartosc ? 'checked' : '' }}>
                        <span class="inline-block px-4 py-2 text-sm border border-zinc-700 text-zinc-300 peer-checked:bg-orange-600 peer-checked:border-orange-600 peer-checked:text-white transition" style="border-radius: 2px;">{{ $opis }}</span>
                    </label>
                @endforeach
            </div>

            <div data-tryb-klienta="istniejacy">
                <label for="pole-szukaj" class="{{ $etykieta }}">SZUKAJ: NAZWISKO, TELEFON, E-MAIL LUB NUMER REJESTRACYJNY</label>
                <input type="search" id="pole-szukaj" autocomplete="off" class="{{ $pole }} mb-3" style="border-radius: 2px;" placeholder="np. Kowalski albo 600…">
                <input type="hidden" name="id_klienta" id="pole-id-klienta" value="{{ old('id_klienta') }}">
                <p id="klienci-komunikat" class="text-sm text-zinc-500">Wpisz co najmniej 2 znaki.</p>
                <div id="klienci" class="space-y-1.5"></div>
            </div>

            <div data-tryb-klienta="nowy" class="hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $etykieta }}">IMIĘ</label>
                        <input type="text" name="imie" value="{{ old('imie') }}" maxlength="50" class="{{ $pole }}" style="border-radius: 2px;">
                    </div>
                    <div>
                        <label class="{{ $etykieta }}">NAZWISKO</label>
                        <input type="text" name="nazwisko" value="{{ old('nazwisko') }}" maxlength="50" class="{{ $pole }}" style="border-radius: 2px;">
                    </div>
                    <div>
                        <label class="{{ $etykieta }}">TELEFON</label>
                        <input type="tel" name="telefon" value="{{ old('telefon') }}" maxlength="15" class="{{ $pole }}" style="border-radius: 2px;">
                    </div>
                    <div>
                        <label class="{{ $etykieta }}">E-MAIL (OPCJONALNIE)</label>
                        <input type="email" name="email" value="{{ old('email') }}" maxlength="100" class="{{ $pole }}" style="border-radius: 2px;">
                    </div>
                </div>
                <p class="mt-4 text-xs text-zinc-500 bg-[#1F2128] border border-zinc-800 px-4 py-3" style="border-radius: 2px;">
                    Zapytaj klienta, czy chce dostać potwierdzenie na e-mail – będzie też mógł sprawdzać historię napraw auta przez internet.
                    Po podaniu adresu klient otrzyma potwierdzenie wizyty i link do ustawienia hasła.
                </p>
            </div>
        </section>

        {{-- KROK 4: Pojazd --}}
        <section data-krok="4" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Pojazd</h2>
            <p id="pojazd-opis" class="text-sm text-zinc-500 mb-5">Wybierz pojazd klienta albo dodaj nowy.</p>

            <div id="pojazdy-klienta" class="space-y-1.5 mb-4"></div>

            <div id="nowy-pojazd" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $etykieta }}">MARKA</label>
                    <input type="text" name="marka" value="{{ old('marka') }}" maxlength="50" class="{{ $pole }}" style="border-radius: 2px;">
                </div>
                <div>
                    <label class="{{ $etykieta }}">MODEL</label>
                    <input type="text" name="model" value="{{ old('model') }}" maxlength="50" class="{{ $pole }}" style="border-radius: 2px;">
                </div>
                <div>
                    <label class="{{ $etykieta }}">NUMER REJESTRACYJNY</label>
                    <input type="text" name="numer_rejestracyjny" value="{{ old('numer_rejestracyjny') }}" maxlength="15" class="{{ $pole }}" style="border-radius: 2px;">
                </div>
                <div>
                    <label class="{{ $etykieta }}">ROK PRODUKCJI (OPCJONALNIE)</label>
                    <input type="number" name="rok_produkcji" value="{{ old('rok_produkcji') }}" min="1950" max="{{ date('Y') + 1 }}" class="{{ $pole }}" style="border-radius: 2px;">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $etykieta }}">VIN (OPCJONALNIE)</label>
                    <input type="text" name="vin" value="{{ old('vin') }}" maxlength="17" class="{{ $pole }}" style="border-radius: 2px;">
                </div>
            </div>
        </section>

        {{-- KROK 5: Zapis --}}
        <section data-krok="5" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Sprawdź i zapisz</h2>
            <p class="text-sm text-zinc-500 mb-5">Wizyta zostanie zapisana jako zatwierdzona.</p>
            <div id="potwierdzenie" class="bg-[#1F2128] border border-zinc-800 divide-y divide-zinc-800 mb-5" style="border-radius: 2px;"></div>

            <label class="{{ $etykieta }}">UWAGI (OPCJONALNIE)</label>
            <textarea name="uwagi" rows="3" maxlength="500" placeholder="np. klient zgłasza stuki przy hamowaniu"
                class="{{ $pole }}" style="border-radius: 2px;">{{ old('uwagi') }}</textarea>
        </section>

        {{-- Nawigacja --}}
        <div class="flex items-center justify-between mt-8 pt-5 border-t border-zinc-800">
            <button type="button" id="btn-wstecz" class="invisible text-sm font-medium text-zinc-400 hover:text-zinc-200">← Wstecz</button>
            <button type="button" id="btn-dalej" disabled
                class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-orange-600" style="border-radius: 2px;">
                Dalej →
            </button>
            <button type="submit" id="btn-zapisz"
                class="hidden bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition disabled:opacity-40" style="border-radius: 2px;">
                Zapisz wizytę
            </button>
        </div>
    </div>

    {{-- Podsumowanie --}}
    <aside class="bg-[#1F2128] border border-zinc-800 p-5 lg:sticky lg:top-6" style="border-radius: 2px;">
        <p class="text-xs font-mono text-orange-500 mb-3">PODSUMOWANIE</p>
        <p class="text-xs font-mono text-zinc-500 mb-2">USŁUGI (<span id="pods-liczba">0</span>)</p>
        <div id="pods-uslugi" class="space-y-1.5 text-sm mb-4"></div>

        <div class="border-t border-zinc-800 pt-3 space-y-1.5 text-sm">
            <div class="flex justify-between gap-3">
                <span class="text-zinc-400">Łącznie</span>
                <span id="pods-czas" class="text-zinc-100 font-mono">—</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-zinc-400">Szacowana kwota</span>
                <span id="pods-cena" class="text-zinc-100 font-mono">—</span>
            </div>
        </div>

        <div class="border-t border-zinc-800 pt-3 mt-3 space-y-1.5 text-sm">
            <div class="flex justify-between gap-3">
                <span class="text-zinc-400">Termin</span>
                <span id="pods-termin" class="text-zinc-100 text-right">—</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-zinc-400">Mechanik</span>
                <span id="pods-mechanik" class="text-zinc-100 text-right">—</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-zinc-400">Klient</span>
                <span id="pods-klient" class="text-zinc-100 text-right">—</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-zinc-400">Pojazd</span>
                <span id="pods-pojazd" class="text-zinc-100 text-right">—</span>
            </div>
        </div>
    </aside>
</form>

<script>
(function () {
    const form = document.getElementById('formularz');
    const checkboxy = Array.from(form.querySelectorAll('input[name="uslugi[]"]'));
    const poleData = document.getElementById('pole-data');
    const poleMechanik = document.getElementById('pole-mechanik'); // tylko administrator
    const kontenerSlotow = document.getElementById('sloty');
    const komunikatSlotow = document.getElementById('sloty-komunikat');
    const opisDaty = document.getElementById('wybrana-data-opis');
    const poleSzukaj = document.getElementById('pole-szukaj');
    const poleIdKlienta = document.getElementById('pole-id-klienta');
    const listaKlientow = document.getElementById('klienci');
    const komunikatKlientow = document.getElementById('klienci-komunikat');
    const polePojazdTryb = document.getElementById('pole-pojazd-tryb');
    const pojazdyKlienta = document.getElementById('pojazdy-klienta');
    const nowyPojazd = document.getElementById('nowy-pojazd');
    const btnWstecz = document.getElementById('btn-wstecz');
    const btnDalej = document.getElementById('btn-dalej');
    const btnZapisz = document.getElementById('btn-zapisz');

    const urlTerminy = @json(route('pracownik.reservations.nowa.terminy'));
    const urlMechanicy = @json(route('pracownik.reservations.nowa.mechanicy'));
    const urlKlienci = @json(route('pracownik.reservations.nowa.klienci'));
    const czyAdmin = @json($czyAdmin);
    const dniZamkniete = @json($dniZamkniete);
    const dniWolne = @json($dniWolne);
    const dzisiaj = @json(now()->toDateString());
    const maksData = @json($maksData);

    const NAZWY_DNI = ['niedziela', 'poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota'];

    let krok = 1;
    let wybranyKlient = @json($wybranyKlient);   // {id, nazwa, telefon, email, pojazdy}
    let poprzedniaGodzina = @json(old('godzina'));
    let poprzedniPojazd = @json(old('id_pojazdu'));
    let poprzedniMechanik = @json(old('id_mechanika'));
    let numerTerminow = 0, numerKlientow = 0, numerMechanikow = 0, timerSzukania = null;
    let miesiac = poleData.value ? poczatekMiesiaca(poleData.value) : poczatekMiesiaca(dzisiaj);

    // ---------- Pomocnicze ----------

    function pad(n) { return String(n).padStart(2, '0'); }
    function naTekst(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function zTekstu(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); }
    function poczatekMiesiaca(s) { const d = zTekstu(s); return new Date(d.getFullYear(), d.getMonth(), 1); }

    function formatCzas(minuty) {
        if (minuty < 60) return minuty + ' min';
        const h = Math.floor(minuty / 60), m = minuty % 60;
        return h + ' h' + (m ? ' ' + m + ' min' : '');
    }
    function formatData(s) {
        const d = zTekstu(s);
        return NAZWY_DNI[d.getDay()] + ', ' + pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear();
    }
    function dodajMinuty(godzina, minuty) {
        const [h, m] = godzina.split(':').map(Number);
        const suma = h * 60 + m + minuty;
        return pad(Math.floor(suma / 60)) + ':' + pad(suma % 60);
    }

    const zaznaczone = () => checkboxy.filter(c => c.checked);
    const lacznyCzas = () => zaznaczone().reduce((s, c) => s + parseInt(c.dataset.czas, 10), 0);
    const lacznaCena = () => zaznaczone().reduce((s, c) => s + parseFloat(c.dataset.cena), 0);
    const wybranaGodzina = () => (kontenerSlotow.querySelector('input[name="godzina"]:checked') || {}).value || null;
    const trybKlienta = () => form.querySelector('input[name="klient_tryb"]:checked').value;
    const pole = nazwa => form.querySelector('[name="' + nazwa + '"]');
    const wypelnione = (...nazwy) => nazwy.every(n => pole(n) && pole(n).value.trim() !== '');

    function paramsUslug() {
        const p = new URLSearchParams();
        zaznaczone().forEach(c => p.append('uslugi[]', c.value));
        return p;
    }

    function opisMechanika() {
        if (!czyAdmin) return 'Ty';
        return poleMechanik.value ? poleMechanik.options[poleMechanik.selectedIndex].textContent : 'Dowolny';
    }
    function opisKlienta() {
        if (trybKlienta() === 'nowy') {
            return wypelnione('imie', 'nazwisko') ? pole('imie').value.trim() + ' ' + pole('nazwisko').value.trim() + ' (nowy)' : '—';
        }
        return wybranyKlient ? wybranyKlient.nazwa : '—';
    }
    function opisPojazdu() {
        if (polePojazdTryb.value === 'nowy') {
            return wypelnione('marka', 'model', 'numer_rejestracyjny')
                ? pole('marka').value.trim() + ' ' + pole('model').value.trim() + ' (' + pole('numer_rejestracyjny').value.trim() + ')'
                : '—';
        }
        const r = form.querySelector('input[name="id_pojazdu"]:checked');
        return r ? r.dataset.opis : '—';
    }

    // ---------- Kroki ----------

    function krokGotowy(n) {
        if (n === 1) return zaznaczone().length > 0;
        if (n === 2) return !!poleData.value && !!wybranaGodzina();
        if (n === 3) return trybKlienta() === 'nowy' ? wypelnione('imie', 'nazwisko', 'telefon') : !!poleIdKlienta.value;
        if (n === 4) return polePojazdTryb.value === 'nowy'
            ? wypelnione('marka', 'model', 'numer_rejestracyjny')
            : !!form.querySelector('input[name="id_pojazdu"]:checked');
        return true;
    }
    function moznaWejsc(n) {
        for (let i = 1; i < n; i++) if (!krokGotowy(i)) return false;
        return true;
    }

    function odswiezKroki() {
        document.getElementById('krok-licznik').textContent = 'KROK ' + krok + ' Z 5';
        form.querySelectorAll('section[data-krok]').forEach(s => s.classList.toggle('hidden', parseInt(s.dataset.krok, 10) !== krok));

        document.querySelectorAll('[data-krok-wskaznik]').forEach(b => {
            const n = parseInt(b.dataset.krokWskaznik, 10);
            b.querySelector('.krok-kolko').className = 'krok-kolko w-9 h-9 flex items-center justify-center text-sm font-semibold border transition '
                + (n <= krok ? 'bg-orange-600 border-orange-600 text-white' : 'bg-[#1F2128] border-zinc-700 text-zinc-500');
            b.querySelector('.krok-nazwa').className = 'krok-nazwa text-xs ' + (n === krok ? 'text-zinc-100 font-medium' : 'text-zinc-500');
            b.disabled = !moznaWejsc(n);
        });
        document.querySelectorAll('[data-krok-linia]').forEach(l => {
            l.className = 'flex-1 h-0.5 mx-2 mb-5 transition ' + (parseInt(l.dataset.krokLinia, 10) < krok ? 'bg-orange-600' : 'bg-zinc-800');
        });

        btnWstecz.classList.toggle('invisible', krok === 1);
        btnDalej.classList.toggle('hidden', krok === 5);
        btnZapisz.classList.toggle('hidden', krok !== 5);
        btnDalej.disabled = !krokGotowy(krok);
    }

    function przejdzDo(n) {
        if (n < 1 || n > 5 || !moznaWejsc(n)) return;
        krok = n;
        if (krok === 2 && czyAdmin) pobierzMechanikow();
        if (krok === 4) renderujPojazdy();
        if (krok === 5) renderujPotwierdzenie();
        odswiez();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function odswiez() {
        odswiezPodsumowanie();
        odswiezKroki();
    }

    // ---------- Podsumowanie (panel boczny) ----------

    function odswiezPodsumowanie() {
        const wybrane = zaznaczone();
        const lista = document.getElementById('pods-uslugi');
        lista.innerHTML = '';
        document.getElementById('pods-liczba').textContent = wybrane.length;

        if (wybrane.length === 0) {
            const p = document.createElement('p');
            p.className = 'text-zinc-600';
            p.textContent = 'Nie wybrano usług.';
            lista.appendChild(p);
        }
        wybrane.forEach(c => {
            const w = document.createElement('div');
            w.className = 'flex justify-between gap-3';
            const nazwa = document.createElement('span');
            nazwa.className = 'text-zinc-300';
            nazwa.textContent = c.dataset.nazwa;
            const czas = document.createElement('span');
            czas.className = 'text-xs font-mono text-zinc-500 whitespace-nowrap';
            czas.textContent = formatCzas(parseInt(c.dataset.czas, 10));
            w.append(nazwa, czas);
            lista.appendChild(w);
        });

        document.getElementById('pods-czas').textContent = wybrane.length ? formatCzas(lacznyCzas()) : '—';
        document.getElementById('pods-cena').textContent = wybrane.length ? lacznaCena().toFixed(2) + ' zł' : '—';

        const g = wybranaGodzina();
        document.getElementById('pods-termin').textContent = poleData.value ? formatData(poleData.value) + (g ? ', ' + g : '') : '—';
        document.getElementById('pods-mechanik').textContent = opisMechanika();
        document.getElementById('pods-klient').textContent = opisKlienta();
        document.getElementById('pods-pojazd').textContent = opisPojazdu();
    }

    // ---------- Kalendarz ----------

    function renderujKalendarz() {
        const kontener = document.getElementById('kal-dni');
        kontener.innerHTML = '';
        document.getElementById('kal-miesiac').textContent = miesiac.toLocaleDateString('pl-PL', { month: 'long', year: 'numeric' });

        const przesuniecie = (miesiac.getDay() + 6) % 7;
        for (let i = 0; i < przesuniecie; i++) kontener.appendChild(document.createElement('span'));

        const ileDni = new Date(miesiac.getFullYear(), miesiac.getMonth() + 1, 0).getDate();
        for (let d = 1; d <= ileDni; d++) {
            const data = new Date(miesiac.getFullYear(), miesiac.getMonth(), d);
            const tekst = naTekst(data);
            const iso = data.getDay() === 0 ? 7 : data.getDay();
            const dzienWolny = dniWolne.includes(tekst);
            const niedostepny = tekst < dzisiaj || tekst > maksData || dniZamkniete.includes(iso) || dzienWolny;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = d;
            btn.dataset.data = tekst;
            btn.disabled = niedostepny;
            if (dzienWolny) btn.title = 'Dzień wolny warsztatu';

            let klasy = 'h-9 text-sm transition ';
            if (tekst === poleData.value) klasy += 'bg-orange-600 text-white font-semibold';
            else if (niedostepny) klasy += 'text-zinc-700 cursor-not-allowed' + (dzienWolny ? ' line-through' : '');
            else klasy += 'text-zinc-200 hover:bg-white/10';
            if (tekst === dzisiaj && tekst !== poleData.value) klasy += ' ring-1 ring-inset ring-zinc-600';

            btn.className = klasy;
            btn.style.borderRadius = '2px';
            kontener.appendChild(btn);
        }

        document.getElementById('kal-poprzedni').disabled = miesiac <= poczatekMiesiaca(dzisiaj);
        document.getElementById('kal-nastepny').disabled = miesiac >= poczatekMiesiaca(maksData);
    }

    function wybierzDate(tekst) {
        poleData.value = tekst;
        poprzedniaGodzina = null;
        renderujKalendarz();
        pobierzGodziny();
    }

    // ---------- Mechanik (administrator) ----------

    async function pobierzMechanikow() {
        if (!czyAdmin || zaznaczone().length === 0) return;
        const przed = poleMechanik.value;
        const obecny = przed || poprzedniMechanik || '';
        const numer = ++numerMechanikow;

        try {
            const odp = await fetch(urlMechanicy + '?' + paramsUslug(), { headers: { Accept: 'application/json' } });
            const dane = await odp.json();
            if (numer !== numerMechanikow || !odp.ok) return;

            poleMechanik.querySelectorAll('option:not([value=""])').forEach(o => o.remove());
            dane.mechanicy.forEach(m => poleMechanik.add(new Option(m.nazwa, m.id)));
            const nadal = dane.mechanicy.some(m => String(m.id) === String(obecny));
            poleMechanik.value = nadal ? String(obecny) : '';
            poprzedniMechanik = null;

            if (poleMechanik.value !== przed && poleData.value) pobierzGodziny();
            odswiezPodsumowanie();
        } catch (e) { /* zostaje „dowolny mechanik” */ }
    }

    // ---------- Godziny ----------

    function pokazKomunikat(tekst) {
        kontenerSlotow.innerHTML = '';
        komunikatSlotow.textContent = tekst;
        komunikatSlotow.classList.remove('hidden');
        odswiez();
    }

    function renderujGodziny(godziny, zachowaj) {
        kontenerSlotow.innerHTML = '';
        komunikatSlotow.classList.add('hidden');

        godziny.forEach(godzina => {
            const label = document.createElement('label');
            label.className = 'cursor-pointer';
            const input = document.createElement('input');
            input.type = 'radio'; input.name = 'godzina'; input.value = godzina; input.className = 'peer sr-only';
            if (godzina === zachowaj) input.checked = true;
            const kafelek = document.createElement('div');
            kafelek.className = 'text-center text-sm py-2 border border-zinc-700 text-zinc-300 hover:border-zinc-500 peer-checked:bg-orange-600 peer-checked:border-orange-600 peer-checked:text-white transition';
            kafelek.style.borderRadius = '2px';
            kafelek.textContent = godzina;
            label.append(input, kafelek);
            kontenerSlotow.appendChild(label);
        });

        poprzedniaGodzina = null;
        odswiez();
    }

    async function pobierzGodziny() {
        if (!poleData.value) { opisDaty.textContent = ''; pokazKomunikat('Wybierz dzień w kalendarzu.'); return; }
        opisDaty.textContent = formatData(poleData.value) + ' · łączny czas: ' + formatCzas(lacznyCzas());
        if (zaznaczone().length === 0) { pokazKomunikat('Najpierw wybierz usługi w kroku 1.'); return; }

        const params = paramsUslug();
        params.append('data', poleData.value);
        if (czyAdmin && poleMechanik.value) params.append('mechanik', poleMechanik.value);

        const zachowaj = wybranaGodzina() || poprzedniaGodzina;
        const numer = ++numerTerminow;
        pokazKomunikat('Szukam wolnych terminów…');

        try {
            const odp = await fetch(urlTerminy + '?' + params, { headers: { Accept: 'application/json' } });
            const dane = await odp.json();
            if (numer !== numerTerminow) return;

            if (!odp.ok) {
                const bledy = dane.errors ? Object.values(dane.errors).flat() : [];
                pokazKomunikat(bledy[0] || 'Nie udało się pobrać terminów. Spróbuj ponownie.');
                return;
            }
            if (dane.godziny.length === 0) { pokazKomunikat(dane.komunikat || 'Brak wolnych terminów w wybranym dniu.'); return; }

            renderujGodziny(dane.godziny, zachowaj);
        } catch (e) {
            if (numer === numerTerminow) pokazKomunikat('Nie udało się pobrać terminów. Sprawdź połączenie i spróbuj ponownie.');
        }
    }

    // ---------- Klient ----------

    function przelaczTrybKlienta() {
        const tryb = trybKlienta();
        form.querySelectorAll('[data-tryb-klienta]').forEach(d => {
            const aktywny = d.dataset.trybKlienta === tryb;
            d.classList.toggle('hidden', !aktywny);
            // Pola nieaktywnego trybu nie są wysyłane
            d.querySelectorAll('input').forEach(i => { if (i.id !== 'pole-szukaj') i.disabled = !aktywny; });
        });
        if (tryb === 'nowy') polePojazdTryb.value = 'nowy';
        else if (wybranyKlient && wybranyKlient.pojazdy.length) polePojazdTryb.value = 'istniejacy';
        odswiez();
    }

    function wybierzKlienta(k) {
        wybranyKlient = k;
        poleIdKlienta.value = k.id;
        polePojazdTryb.value = k.pojazdy.length ? 'istniejacy' : 'nowy';
        poprzedniPojazd = null;
        renderujKlientow([k]);
        komunikatKlientow.classList.add('hidden');
        odswiez();
    }

    function renderujKlientow(klienci) {
        listaKlientow.innerHTML = '';
        klienci.forEach(k => {
            const wybrany = String(poleIdKlienta.value) === String(k.id);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left px-4 py-2.5 border transition '
                + (wybrany ? 'border-orange-600 bg-orange-600/10' : 'border-zinc-800 bg-[#1F2128] hover:border-zinc-600');
            btn.style.borderRadius = '2px';
            const nazwa = document.createElement('p');
            nazwa.className = 'text-sm font-medium text-zinc-100';
            nazwa.textContent = k.nazwa + (wybrany ? '  ✓' : '');
            const opis = document.createElement('p');
            opis.className = 'text-xs text-zinc-500';
            opis.textContent = [k.telefon, k.email, k.pojazdy.map(p => p.opis).join(', ')].filter(Boolean).join(' · ');
            btn.append(nazwa, opis);
            btn.addEventListener('click', () => wybierzKlienta(k));
            listaKlientow.appendChild(btn);
        });
    }

    function szukajKlientow() {
        const fraza = poleSzukaj.value.trim();
        clearTimeout(timerSzukania);
        if (fraza.length < 2) {
            if (!wybranyKlient) {
                listaKlientow.innerHTML = '';
                komunikatKlientow.textContent = 'Wpisz co najmniej 2 znaki.';
                komunikatKlientow.classList.remove('hidden');
            }
            return;
        }
        timerSzukania = setTimeout(async () => {
            const numer = ++numerKlientow;
            try {
                const odp = await fetch(urlKlienci + '?q=' + encodeURIComponent(fraza), { headers: { Accept: 'application/json' } });
                const dane = await odp.json();
                if (numer !== numerKlientow) return;
                renderujKlientow(dane.klienci);
                komunikatKlientow.textContent = 'Nie znaleziono klienta – dodaj go jako nowego.';
                komunikatKlientow.classList.toggle('hidden', dane.klienci.length > 0);
            } catch (e) { /* kolejna próba przy następnym znaku */ }
        }, 300);
    }

    // ---------- Pojazd ----------

    function renderujPojazdy() {
        pojazdyKlienta.innerHTML = '';
        const pojazdy = trybKlienta() === 'istniejacy' && wybranyKlient ? wybranyKlient.pojazdy : [];
        if (pojazdy.length === 0) polePojazdTryb.value = 'nowy';

        document.getElementById('pojazd-opis').textContent = pojazdy.length
            ? 'Wybierz pojazd klienta albo dodaj nowy.'
            : 'Klient nie ma jeszcze pojazdów – dodaj pojazd.';

        pojazdy.forEach(p => pojazdyKlienta.appendChild(opcjaPojazdu(p.id, p.opis)));
        if (pojazdy.length) pojazdyKlienta.appendChild(opcjaPojazdu('nowy', '+ Nowy pojazd'));

        if (polePojazdTryb.value === 'nowy') {
            const nowy = pojazdyKlienta.querySelector('input[value="nowy"]');
            if (nowy) nowy.checked = true;
        }
        pokazNowyPojazd();
    }

    function opcjaPojazdu(wartosc, opis) {
        const label = document.createElement('label');
        label.className = 'block cursor-pointer';
        const input = document.createElement('input');
        input.type = 'radio';
        input.name = wartosc === 'nowy' ? 'pojazd_wybor' : 'id_pojazdu';
        input.value = wartosc;
        input.dataset.opis = opis;
        input.className = 'peer sr-only';
        if (wartosc !== 'nowy' && String(wartosc) === String(poprzedniPojazd)) input.checked = true;
        input.addEventListener('change', () => {
            polePojazdTryb.value = wartosc === 'nowy' ? 'nowy' : 'istniejacy';
            pojazdyKlienta.querySelectorAll('input').forEach(i => { if (i !== input) i.checked = false; });
            pokazNowyPojazd();
        });
        const div = document.createElement('div');
        div.className = 'px-4 py-3 text-sm font-medium text-zinc-100 bg-[#1F2128] border border-zinc-800 hover:border-zinc-600 peer-checked:border-orange-600 peer-checked:bg-orange-600/10 transition';
        div.style.borderRadius = '2px';
        div.textContent = opis;
        label.append(input, div);
        return label;
    }

    function pokazNowyPojazd() {
        const nowy = polePojazdTryb.value === 'nowy';
        nowyPojazd.classList.toggle('hidden', !nowy);
        nowyPojazd.querySelectorAll('input').forEach(i => i.disabled = !nowy);
        odswiez();
    }

    // ---------- Potwierdzenie ----------

    function wiersz(etykieta, wartosc, wyroznij = false) {
        const div = document.createElement('div');
        div.className = 'flex justify-between gap-6 px-5 py-3 text-sm';
        const e = document.createElement('span');
        e.className = 'text-zinc-400';
        e.textContent = etykieta;
        const w = document.createElement('span');
        w.className = 'text-right ' + (wyroznij ? 'text-zinc-50 font-semibold' : 'text-zinc-200');
        w.style.whiteSpace = 'pre-line';
        w.textContent = wartosc;
        div.append(e, w);
        return div;
    }

    function renderujPotwierdzenie() {
        const kontener = document.getElementById('potwierdzenie');
        kontener.innerHTML = '';
        const g = wybranaGodzina();

        let kontakt;
        if (trybKlienta() === 'nowy') {
            kontakt = pole('telefon').value.trim() + '\n' + (pole('email').value.trim() || 'bez e-maila – brak potwierdzenia');
        } else {
            kontakt = [wybranyKlient.telefon, wybranyKlient.email || 'bez e-maila – brak potwierdzenia'].filter(Boolean).join('\n');
        }

        kontener.append(
            wiersz('Usługi', zaznaczone().map(c => c.dataset.nazwa + ' – ' + parseFloat(c.dataset.cena).toFixed(2) + ' zł').join('\n')),
            wiersz('Termin', formatData(poleData.value) + '\n' + g + '–' + dodajMinuty(g, lacznyCzas())),
            wiersz('Mechanik', opisMechanika()),
            wiersz('Klient', opisKlienta()),
            wiersz('Kontakt', kontakt),
            wiersz('Pojazd', opisPojazdu() + (polePojazdTryb.value === 'nowy' ? ' – nowy' : '')),
            wiersz('Szacowana kwota', lacznaCena().toFixed(2) + ' zł', true)
        );
    }

    // ---------- Zdarzenia ----------

    checkboxy.forEach(c => c.addEventListener('change', () => {
        if (czyAdmin) pobierzMechanikow();
        if (poleData.value) pobierzGodziny(); else odswiez();
    }));
    kontenerSlotow.addEventListener('change', odswiez);
    if (czyAdmin) poleMechanik.addEventListener('change', () => { if (poleData.value) pobierzGodziny(); else odswiez(); });

    document.getElementById('kal-dni').addEventListener('click', e => {
        const btn = e.target.closest('button[data-data]');
        if (btn && !btn.disabled) wybierzDate(btn.dataset.data);
    });
    document.getElementById('kal-poprzedni').addEventListener('click', () => {
        miesiac = new Date(miesiac.getFullYear(), miesiac.getMonth() - 1, 1);
        renderujKalendarz();
    });
    document.getElementById('kal-nastepny').addEventListener('click', () => {
        miesiac = new Date(miesiac.getFullYear(), miesiac.getMonth() + 1, 1);
        renderujKalendarz();
    });

    form.querySelectorAll('input[name="klient_tryb"]').forEach(r => r.addEventListener('change', przelaczTrybKlienta));
    poleSzukaj.addEventListener('input', szukajKlientow);
    form.addEventListener('input', e => { if (e.target.closest('[data-tryb-klienta="nowy"], #nowy-pojazd')) odswiez(); });

    btnDalej.addEventListener('click', () => { if (krokGotowy(krok)) przejdzDo(krok + 1); });
    btnWstecz.addEventListener('click', () => przejdzDo(krok - 1));
    document.querySelectorAll('[data-krok-wskaznik]').forEach(b => {
        b.addEventListener('click', () => przejdzDo(parseInt(b.dataset.krokWskaznik, 10)));
    });

    form.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') e.preventDefault(); });
    form.addEventListener('submit', e => {
        if (krok !== 5 || !moznaWejsc(5)) { e.preventDefault(); return; }
        btnZapisz.disabled = true;
        btnZapisz.textContent = 'Zapisywanie…';
    });

    // ---------- Start ----------

    przelaczTrybKlienta();
    if (wybranyKlient) {
        poleIdKlienta.value = wybranyKlient.id;
        renderujKlientow([wybranyKlient]);
        komunikatKlientow.classList.add('hidden');
    }
    renderujKalendarz();
    if (poleData.value && zaznaczone().length) pobierzGodziny();
    if (czyAdmin && zaznaczone().length) pobierzMechanikow();

    // Po błędzie walidacji otwieramy krok, którego dotyczy błąd (o ile poprzednie są kompletne)
    const bledy = @json($errors->keys());
    const krokBledu = k => k.startsWith('uslugi') ? 1
        : ['data', 'godzina', 'id_mechanika'].includes(k) ? 2
        : ['klient_tryb', 'id_klienta', 'imie', 'nazwisko', 'telefon', 'email'].includes(k) ? 3
        : ['pojazd_tryb', 'id_pojazdu', 'marka', 'model', 'numer_rejestracyjny', 'rok_produkcji', 'vin'].includes(k) ? 4 : 5;
    if (bledy.length) {
        const cel = Math.min(...bledy.map(krokBledu));
        krok = 1;
        for (let n = 1; n <= cel; n++) if (moznaWejsc(n)) krok = n;
        if (krok === 4) renderujPojazdy();
    }
    odswiez();
})();
</script>
@endsection
