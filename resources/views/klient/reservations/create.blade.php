@extends('layouts.klient')
@section('title', 'Nowa rezerwacja')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">NOWA REZERWACJA</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Umów wizytę</h1>

@if ($pojazdy->isEmpty())
    <div class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg" style="border-radius: 2px;">
        <p class="text-zinc-400 mb-4">Musisz najpierw dodać pojazd, zanim umówisz wizytę.</p>
        <a href="{{ route('klient.vehicles.create') }}" class="text-orange-500 hover:text-orange-400 font-medium">
            Dodaj pojazd →
        </a>
    </div>
@else

{{-- Pasek kroków --}}
<div class="max-w-4xl mb-8">
    <p id="krok-licznik" class="text-xs font-mono text-zinc-500 text-center mb-3">KROK 1 Z 4</p>
    <div class="flex items-center">
        @foreach ([1 => 'Usługi', 2 => 'Termin', 3 => 'Pojazd', 4 => 'Potwierdzenie'] as $nr => $nazwa)
            <button type="button" data-krok-wskaznik="{{ $nr }}" class="flex flex-col items-center gap-1.5 shrink-0 disabled:cursor-not-allowed">
                <span class="krok-kolko w-9 h-9 flex items-center justify-center text-sm font-semibold border bg-[#1F2128] border-zinc-700 text-zinc-500" style="border-radius: 50%;">{{ $nr }}</span>
                <span class="krok-nazwa text-xs text-zinc-500">{{ $nazwa }}</span>
            </button>
            @if ($nr < 4)
                <div data-krok-linia="{{ $nr }}" class="flex-1 h-0.5 mx-2 mb-5 bg-zinc-800"></div>
            @endif
        @endforeach
    </div>
</div>

<form id="formularz-rezerwacji" method="POST" action="{{ route('klient.reservations.store') }}"
      class="max-w-4xl grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6 items-start">
    @csrf
    <input type="hidden" name="data" id="pole-data" value="{{ old('data') }}">

    <div>
                {{-- KROK 1: Usługi --}}
        <section data-krok="1">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Wybierz usługi</h2>
            <p class="text-sm text-zinc-500 mb-5">Możesz wybrać kilka usług – zostaną wykonane jedna po drugiej podczas jednej wizyty.</p>

            @error('uslugi') <p class="text-sm text-red-500 mb-4">{{ $message }}</p> @enderror
            @if ($errors->has('uslugi.*')) <p class="text-sm text-red-500 mb-4">{{ $errors->first('uslugi.*') }}</p> @endif

            <div class="space-y-1.5">
                @foreach ($uslugi as $u)
                    @php
                        $h = intdiv($u->czas_trwania, 60);
                        $m = $u->czas_trwania % 60;
                        $czasOpis = trim(($h ? $h . ' h ' : '') . ($m ? $m . ' min' : ''));
                    @endphp
                    <label class="block cursor-pointer" @if ($u->opis) title="{{ $u->opis }}" @endif>
                        <input type="checkbox" name="uslugi[]" value="{{ $u->id_uslugi }}"
                               data-nazwa="{{ $u->nazwa }}" data-czas="{{ $u->czas_trwania }}" data-cena="{{ $u->cena }}"
                               class="peer sr-only"
                               {{ in_array($u->id_uslugi, old('uslugi', [])) ? 'checked' : '' }}>
                        <div class="flex items-center justify-between gap-4 px-4 py-2.5 bg-[#1F2128] border border-zinc-800 hover:border-zinc-600 peer-checked:border-orange-600 peer-checked:bg-orange-600/10 transition" style="border-radius: 2px;">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-100">
                                    {{ $u->nazwa }}
                                    <span class="ml-2 text-xs font-mono text-zinc-500 font-normal">{{ $czasOpis }}</span>
                                </p>
                                @if ($u->opis)
                                    <p class="text-xs text-zinc-500 truncate">{{ $u->opis }}</p>
                                @endif
                            </div>
                            <p class="text-sm font-semibold text-zinc-100 whitespace-nowrap">{{ number_format($u->cena, 2) }} zł</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- KROK 2: Termin --}}
        <section data-krok="2" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Wybierz termin</h2>
            <p class="text-sm text-zinc-500 mb-5">Dni zamknięte i dni wolne warsztatu są wyszarzone.</p>

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
                    @error('data') <p class="text-sm text-red-500 mt-3">{{ $message }}</p> @enderror
                    @error('godzina') <p class="text-sm text-red-500 mt-3">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- KROK 3: Pojazd --}}
        <section data-krok="3" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Wybierz pojazd</h2>
            <p class="text-sm text-zinc-500 mb-5">Którym samochodem przyjedziesz na wizytę?</p>

            <div class="space-y-2 mb-6">
                @foreach ($pojazdy as $p)
                    <label class="block cursor-pointer">
                        <input type="radio" name="pojazd_id" value="{{ $p->id_pojazdu }}"
                               data-opis="{{ $p->marka }} {{ $p->model }} ({{ $p->numer_rejestracyjny }})"
                               class="peer sr-only"
                               {{ (string) old('pojazd_id', request('pojazd')) === (string) $p->id_pojazdu ? 'checked' : '' }}>
                        <div class="flex items-center justify-between gap-4 px-4 py-3 bg-[#1F2128] border border-zinc-800 hover:border-zinc-600 peer-checked:border-orange-600 peer-checked:bg-orange-600/10 transition" style="border-radius: 2px;">
                            <div>
                                <p class="font-medium text-zinc-100">{{ $p->marka }} {{ $p->model }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">{{ $p->rok_produkcji ? $p->rok_produkcji . ' · ' : '' }}{{ $p->vin ? 'VIN ' . $p->vin : '' }}</p>
                            </div>
                            <p class="text-sm font-mono text-zinc-300 whitespace-nowrap">{{ $p->numer_rejestracyjny }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('pojazd_id') <p class="text-sm text-red-500 mb-4">{{ $message }}</p> @enderror

            <label class="block text-xs font-mono text-zinc-400 mb-1.5">UWAGI DLA WARSZTATU (OPCJONALNIE)</label>
            <textarea name="uwagi" rows="3" maxlength="500" placeholder="np. stuki przy hamowaniu, auto zostawię na cały dzień"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">{{ old('uwagi') }}</textarea>
            @error('uwagi') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </section>

        {{-- KROK 4: Potwierdzenie --}}
        <section data-krok="4" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Potwierdź rezerwację</h2>
            <p class="text-sm text-zinc-500 mb-5">Sprawdź szczegóły. Rezerwacja trafi do warsztatu jako oczekująca – o zatwierdzeniu poinformujemy Cię e-mailem.</p>
            <div id="potwierdzenie" class="bg-[#1F2128] border border-zinc-800 divide-y divide-zinc-800" style="border-radius: 2px;"></div>
        </section>

        {{-- Nawigacja --}}
        <div class="flex items-center justify-between mt-8 pt-5 border-t border-zinc-800">
            <button type="button" id="btn-wstecz" class="invisible text-sm font-medium text-zinc-400 hover:text-zinc-200">← Wstecz</button>
            <button type="button" id="btn-dalej" disabled
                class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-orange-600" style="border-radius: 2px;">
                Dalej →
            </button>
            <button type="submit" id="btn-potwierdz"
                class="hidden bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition disabled:opacity-40" style="border-radius: 2px;">
                Potwierdź rezerwację
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
                <span class="text-zinc-400">Pojazd</span>
                <span id="pods-pojazd" class="text-zinc-100 text-right">—</span>
            </div>
        </div>
    </aside>
</form>

<script>
(function () {
    const form = document.getElementById('formularz-rezerwacji');
    const checkboxy = Array.from(form.querySelectorAll('input[name="uslugi[]"]'));
    const radiaPojazdu = Array.from(form.querySelectorAll('input[name="pojazd_id"]'));
    const poleData = document.getElementById('pole-data');
    const poleUwagi = form.querySelector('textarea[name="uwagi"]');
    const kontenerSlotow = document.getElementById('sloty');
    const komunikatSlotow = document.getElementById('sloty-komunikat');
    const opisDaty = document.getElementById('wybrana-data-opis');
    const btnWstecz = document.getElementById('btn-wstecz');
    const btnDalej = document.getElementById('btn-dalej');
    const btnPotwierdz = document.getElementById('btn-potwierdz');

    const url = @json(route('klient.reservations.availability'));
    const dniZamkniete = @json($dniZamkniete);
    const dniWolne = @json($dniWolne);
    const dzisiaj = @json(now()->toDateString());
    const maksData = @json($maksData);
    const bledy = @json($errors->keys());

    const NAZWY_DNI = ['niedziela', 'poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota'];

    let krok = 1;
    let poprzedniaGodzina = @json(old('godzina'));
    let numerZapytania = 0;

    // ---------- Pomocnicze ----------

    function pad(n) { return String(n).padStart(2, '0'); }
    function naTekst(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function zTekstu(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); }
    function poczatekMiesiaca(s) { const d = zTekstu(s); return new Date(d.getFullYear(), d.getMonth(), 1); }

    function formatCzas(minuty) {
        if (minuty < 60) return minuty + ' min';
        const h = Math.floor(minuty / 60);
        const m = minuty % 60;
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

    function zaznaczoneUslugi() { return checkboxy.filter(c => c.checked); }
    function lacznyCzas() { return zaznaczoneUslugi().reduce((s, c) => s + parseInt(c.dataset.czas, 10), 0); }
    function lacznaCena() { return zaznaczoneUslugi().reduce((s, c) => s + parseFloat(c.dataset.cena), 0); }
    function wybranaGodzina() {
        const r = kontenerSlotow.querySelector('input[name="godzina"]:checked');
        return r ? r.value : null;
    }
    function wybranyPojazd() { return radiaPojazdu.find(r => r.checked) || null; }

    let miesiac = poczatekMiesiaca(poleData.value || dzisiaj);

    // ---------- Kroki ----------

    function krokGotowy(n) {
        if (n === 1) return zaznaczoneUslugi().length > 0;
        if (n === 2) return !!poleData.value && !!wybranaGodzina();
        if (n === 3) return !!wybranyPojazd();
        return true;
    }

    function moznaWejsc(n) {
        for (let i = 1; i < n; i++) {
            if (!krokGotowy(i)) return false;
        }
        return true;
    }

    function odswiezKroki() {
        document.getElementById('krok-licznik').textContent = 'KROK ' + krok + ' Z 4';

        document.querySelectorAll('section[data-krok]').forEach(s => {
            s.classList.toggle('hidden', parseInt(s.dataset.krok, 10) !== krok);
        });

        document.querySelectorAll('[data-krok-wskaznik]').forEach(b => {
            const n = parseInt(b.dataset.krokWskaznik, 10);
            const kolko = b.querySelector('.krok-kolko');
            const nazwa = b.querySelector('.krok-nazwa');

            kolko.className = 'krok-kolko w-9 h-9 flex items-center justify-center text-sm font-semibold border transition '
                + (n <= krok ? 'bg-orange-600 border-orange-600 text-white' : 'bg-[#1F2128] border-zinc-700 text-zinc-500');
            nazwa.className = 'krok-nazwa text-xs ' + (n === krok ? 'text-zinc-100 font-medium' : 'text-zinc-500');
            b.disabled = !moznaWejsc(n);
        });

        document.querySelectorAll('[data-krok-linia]').forEach(l => {
            l.className = 'flex-1 h-0.5 mx-2 mb-5 transition '
                + (parseInt(l.dataset.krokLinia, 10) < krok ? 'bg-orange-600' : 'bg-zinc-800');
        });

        btnWstecz.classList.toggle('invisible', krok === 1);
        btnDalej.classList.toggle('hidden', krok === 4);
        btnPotwierdz.classList.toggle('hidden', krok !== 4);
        btnDalej.disabled = !krokGotowy(krok);
    }

    function przejdzDo(n) {
        if (n < 1 || n > 4 || !moznaWejsc(n)) return;
        krok = n;
        if (krok === 4) renderujPotwierdzenie();
        odswiezKroki();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ---------- Podsumowanie ----------

    function odswiezPodsumowanie() {
        const wybrane = zaznaczoneUslugi();
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
            const wiersz = document.createElement('div');
            wiersz.className = 'flex justify-between gap-3';
            const nazwa = document.createElement('span');
            nazwa.className = 'text-zinc-300';
            nazwa.textContent = c.dataset.nazwa;
            const czas = document.createElement('span');
            czas.className = 'text-xs font-mono text-zinc-500 whitespace-nowrap';
            czas.textContent = formatCzas(parseInt(c.dataset.czas, 10));
            wiersz.append(nazwa, czas);
            lista.appendChild(wiersz);
        });

        document.getElementById('pods-czas').textContent = wybrane.length ? formatCzas(lacznyCzas()) : '—';
        document.getElementById('pods-cena').textContent = wybrane.length ? lacznaCena().toFixed(2) + ' zł' : '—';

        const g = wybranaGodzina();
        document.getElementById('pods-termin').textContent = poleData.value
            ? formatData(poleData.value) + (g ? ', ' + g : '')
            : '—';

        const p = wybranyPojazd();
        document.getElementById('pods-pojazd').textContent = p ? p.dataset.opis : '—';
    }

    function odswiez() {
        odswiezPodsumowanie();
        odswiezKroki();
    }

    // ---------- Kalendarz ----------

    function renderujKalendarz() {
        const kontener = document.getElementById('kal-dni');
        kontener.innerHTML = '';

        document.getElementById('kal-miesiac').textContent =
            miesiac.toLocaleDateString('pl-PL', { month: 'long', year: 'numeric' });

        // Pusta przestrzeń przed 1. dniem miesiąca (tydzień od poniedziałku)
        const przesuniecie = (miesiac.getDay() + 6) % 7;
        for (let i = 0; i < przesuniecie; i++) {
            kontener.appendChild(document.createElement('span'));
        }

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
            if (tekst === poleData.value) {
                klasy += 'bg-orange-600 text-white font-semibold';
            } else if (niedostepny) {
                klasy += 'text-zinc-700 cursor-not-allowed' + (dzienWolny ? ' line-through' : '');
            } else {
                klasy += 'text-zinc-200 hover:bg-white/10';
            }
            if (tekst === dzisiaj && tekst !== poleData.value) {
                klasy += ' ring-1 ring-inset ring-zinc-600';
            }

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

    // ---------- Godziny ----------

    function pokazKomunikat(tekst) {
        kontenerSlotow.innerHTML = '';
        komunikatSlotow.textContent = tekst;
        komunikatSlotow.classList.remove('hidden');
        odswiez();
    }

    function renderujGodziny(godziny) {
        kontenerSlotow.innerHTML = '';
        komunikatSlotow.classList.add('hidden');

        godziny.forEach(godzina => {
            const label = document.createElement('label');
            label.className = 'cursor-pointer';

            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'godzina';
            input.value = godzina;
            input.className = 'peer sr-only';
            if (godzina === poprzedniaGodzina) input.checked = true;

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
        const wybrane = zaznaczoneUslugi();

        if (!poleData.value) {
            opisDaty.textContent = '';
            pokazKomunikat('Wybierz dzień w kalendarzu.');
            return;
        }

        opisDaty.textContent = formatData(poleData.value) + ' · łączny czas: ' + formatCzas(lacznyCzas());

        if (wybrane.length === 0) {
            pokazKomunikat('Najpierw wybierz usługi w kroku 1.');
            return;
        }

        const params = new URLSearchParams();
        wybrane.forEach(c => params.append('uslugi[]', c.value));
        params.append('data', poleData.value);

        const numer = ++numerZapytania;
        pokazKomunikat('Szukam wolnych terminów…');

        try {
            const odp = await fetch(url + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            const dane = await odp.json();

            // Ignorujemy odpowiedź, jeśli w międzyczasie wybór się zmienił
            if (numer !== numerZapytania) return;

            if (!odp.ok) {
                const komunikaty = dane.errors ? Object.values(dane.errors).flat() : [];
                pokazKomunikat(komunikaty[0] || 'Nie udało się pobrać terminów. Spróbuj ponownie.');
                return;
            }

            if (dane.godziny.length === 0) {
                pokazKomunikat(dane.komunikat || 'Brak wolnych terminów w wybranym dniu.');
                return;
            }

            renderujGodziny(dane.godziny);
        } catch (e) {
            if (numer === numerZapytania) {
                pokazKomunikat('Nie udało się pobrać terminów. Sprawdź połączenie i spróbuj ponownie.');
            }
        }
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

        const uslugi = zaznaczoneUslugi()
            .map(c => c.dataset.nazwa + ' – ' + parseFloat(c.dataset.cena).toFixed(2) + ' zł')
            .join('\n');
        const g = wybranaGodzina();
        const czas = lacznyCzas();

        kontener.append(
            wiersz('Usługi', uslugi),
            wiersz('Termin', formatData(poleData.value) + '\n' + g + '–' + dodajMinuty(g, czas)),
            wiersz('Czas trwania', formatCzas(czas)),
            wiersz('Pojazd', wybranyPojazd().dataset.opis)
        );

        if (poleUwagi.value.trim()) {
            kontener.append(wiersz('Uwagi', poleUwagi.value.trim()));
        }

        kontener.append(wiersz('Szacowana kwota', lacznaCena().toFixed(2) + ' zł', true));
    }

    // ---------- Zdarzenia ----------

    checkboxy.forEach(c => c.addEventListener('change', () => {
        odswiezPodsumowanie();
        if (poleData.value) {
            // Zmiana usług zmienia czas wizyty – godziny trzeba pobrać ponownie
            poprzedniaGodzina = wybranaGodzina();
            pobierzGodziny();
        } else {
            odswiezKroki();
        }
    }));

    kontenerSlotow.addEventListener('change', odswiez);
    radiaPojazdu.forEach(r => r.addEventListener('change', odswiez));

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

    btnDalej.addEventListener('click', () => {
        if (krokGotowy(krok)) przejdzDo(krok + 1);
    });
    btnWstecz.addEventListener('click', () => przejdzDo(krok - 1));
    document.querySelectorAll('[data-krok-wskaznik]').forEach(b => {
        b.addEventListener('click', () => przejdzDo(parseInt(b.dataset.krokWskaznik, 10)));
    });

    form.addEventListener('submit', e => {
        if (krok !== 4 || !moznaWejsc(4)) {
            e.preventDefault();
            return;
        }
        // Ochrona przed podwójnym wysłaniem
        btnPotwierdz.disabled = true;
        btnPotwierdz.textContent = 'Wysyłanie…';
    });

    // Enter w polach (poza uwagami) nie wysyła formularza przedwcześnie
    form.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') e.preventDefault();
    });

    // ---------- Start ----------

    if (!wybranyPojazd() && radiaPojazdu.length) {
        radiaPojazdu[0].checked = true;
    }

    renderujKalendarz();
    odswiezPodsumowanie();

    if (poleData.value && zaznaczoneUslugi().length) {
        pobierzGodziny();
    }

    // Po błędzie walidacji otwieramy krok, którego błąd dotyczy
    if (bledy.some(k => k.startsWith('uslugi'))) {
        krok = 1;
    } else if (bledy.some(k => k === 'data' || k === 'godzina')) {
        krok = 2;
    } else if (bledy.some(k => k === 'pojazd_id' || k === 'uwagi')) {
        krok = 3;
    }

    odswiezKroki();
})();
</script>

@endif
@endsection