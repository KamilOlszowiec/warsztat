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

<x-kroki :nazwy="['Usługi', 'Termin', 'Pojazd', 'Potwierdzenie']" />

<form id="formularz-rezerwacji" method="POST" action="{{ route('klient.reservations.store') }}"
      class="max-w-4xl grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6 items-start">
    @csrf
    <input type="hidden" name="data" id="pole-data" value="{{ old('data') }}">

    <div>
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

        <section data-krok="2" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Wybierz termin</h2>
            <p class="text-sm text-zinc-500 mb-5">Dni zamknięte i dni wolne warsztatu są wyszarzone.</p>

            <div class="mb-5 max-w-sm">
                <label for="pole-mechanik" class="block text-xs font-mono text-zinc-400 mb-1.5">MECHANIK</label>
                <select id="pole-mechanik" name="id_mechanika"
                        class="w-full bg-[#1F2128] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                    <option value="">Dowolny mechanik (najwcześniejszy termin)</option>
                </select>
                <p class="text-xs text-zinc-500 mt-1.5">Lista zawiera tylko mechaników, którzy wykonują wszystkie wybrane usługi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[minmax(0,300px)_1fr] gap-6">
                <x-kalendarz id="kalendarz" />

                <div>
                    <x-siatka-godzin id="godziny" />
                    @error('data') <p class="text-sm text-red-500 mt-3">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

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

        <section data-krok="4" class="hidden">
            <h2 class="text-lg font-semibold text-zinc-50 mb-1">Potwierdź rezerwację</h2>
            <p class="text-sm text-zinc-500 mb-5">Sprawdź szczegóły. Rezerwacja trafi do warsztatu jako oczekująca – o zatwierdzeniu poinformujemy Cię e-mailem.</p>
            <div id="potwierdzenie" class="bg-[#1F2128] border border-zinc-800 divide-y divide-zinc-800" style="border-radius: 2px;"></div>
        </section>

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

@vite('resources/js/rezerwacje.js')
<script type="module">
const { format, kalendarz, siatkaGodzin, listaMechanikow, kroki } = window.Rezerwacje;

const form = document.getElementById('formularz-rezerwacji');
const checkboxy = Array.from(form.querySelectorAll('input[name="uslugi[]"]'));
const radiaPojazdu = Array.from(form.querySelectorAll('input[name="pojazd_id"]'));
const poleData = document.getElementById('pole-data');
const poleUwagi = form.querySelector('textarea[name="uwagi"]');

const zaznaczone = () => checkboxy.filter(c => c.checked);
const lacznyCzas = () => zaznaczone().reduce((s, c) => s + Number(c.dataset.czas), 0);
const lacznaCena = () => zaznaczone().reduce((s, c) => s + parseFloat(c.dataset.cena), 0);
const wybranyPojazd = () => radiaPojazdu.find(r => r.checked) || null;

function paramsUslug() {
    const p = new URLSearchParams();
    zaznaczone().forEach(c => p.append('uslugi[]', c.value));
    return p;
}

// wspolne elementy 
const mechanicy = listaMechanikow(document.getElementById('pole-mechanik'), {
    url: @json(route('klient.reservations.mechanicy')),
    parametry: () => (zaznaczone().length ? paramsUslug() : null),
    poZmianieWyboru: () => (poleData.value ? godziny.pobierz() : odswiez()),
    poczatkowy: @json(old('id_mechanika')),
});

const godziny = siatkaGodzin(document.getElementById('godziny'), {
    url: @json(route('klient.reservations.availability')),
    zaznacz: @json(old('godzina')),
    opis: () => (poleData.value ? format.data(poleData.value) + ' · łączny czas: ' + format.czas(lacznyCzas()) : ''),
    parametry: () => {
        if (!poleData.value) return 'Wybierz dzień w kalendarzu.';
        if (!zaznaczone().length) return 'Najpierw wybierz usługi w kroku 1.';
        const p = paramsUslug();
        p.append('data', poleData.value);
        const mechanik = document.getElementById('pole-mechanik').value;
        if (mechanik) p.append('mechanik', mechanik);
        return p;
    },
    poZmianie: () => odswiez(),
});

kalendarz(document.getElementById('kalendarz'), poleData, {
    dzisiaj: @json(now()->toDateString()),
    maksData: @json($maksData),
    dniZamkniete: @json($dniZamkniete),
    dniWolne: @json($dniWolne),
    poWyborze: () => godziny.pobierz(),
});

const kreator = kroki(form, {
    liczba: 4,
    gotowy: n => n === 1 ? zaznaczone().length > 0
        : n === 2 ? !!poleData.value && !!godziny.wybrana()
        : n === 3 ? !!wybranyPojazd()
        : true,
    poWejsciu: n => { if (n === 4) renderujPotwierdzenie(); },
    wstecz: document.getElementById('btn-wstecz'),
    dalej: document.getElementById('btn-dalej'),
    zatwierdz: document.getElementById('btn-potwierdz'),
});

function odswiezPodsumowanie() {
    const wybrane = zaznaczone();
    const lista = document.getElementById('pods-uslugi');
    lista.innerHTML = '';
    document.getElementById('pods-liczba').textContent = wybrane.length;

    if (!wybrane.length) {
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
        czas.textContent = format.czas(Number(c.dataset.czas));
        wiersz.append(nazwa, czas);
        lista.appendChild(wiersz);
    });

    document.getElementById('pods-czas').textContent = wybrane.length ? format.czas(lacznyCzas()) : '—';
    document.getElementById('pods-cena').textContent = wybrane.length ? format.kwota(lacznaCena()) : '—';

    const g = godziny.wybrana();
    document.getElementById('pods-termin').textContent = poleData.value ? format.data(poleData.value) + (g ? ', ' + g : '') : '—';
    document.getElementById('pods-pojazd').textContent = wybranyPojazd()?.dataset.opis ?? '—';
}

function odswiez() {
    odswiezPodsumowanie();
    kreator.odswiez();
}

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
    const g = godziny.wybrana();

    kontener.append(
        wiersz('Usługi', zaznaczone().map(c => c.dataset.nazwa + ' – ' + format.kwota(parseFloat(c.dataset.cena))).join('\n')),
        wiersz('Termin', format.data(poleData.value) + '\n' + g + '–' + format.dodajMinuty(g, lacznyCzas())),
        wiersz('Czas trwania', format.czas(lacznyCzas())),
        wiersz('Mechanik', mechanicy.opis()),
        wiersz('Pojazd', wybranyPojazd().dataset.opis)
    );
    if (poleUwagi.value.trim()) kontener.append(wiersz('Uwagi', poleUwagi.value.trim()));
    kontener.append(wiersz('Szacowana kwota', format.kwota(lacznaCena()), true));
}

checkboxy.forEach(c => c.addEventListener('change', () => {
    mechanicy.pobierz();
    // Zmiana usług zmienia czas wizyty 
    if (poleData.value) godziny.pobierz(); else odswiez();
}));
radiaPojazdu.forEach(r => r.addEventListener('change', odswiez));

if (!wybranyPojazd() && radiaPojazdu.length) radiaPojazdu[0].checked = true;

mechanicy.pobierz();
if (poleData.value && zaznaczone().length) godziny.pobierz();

// Po błędzie walidacji otwieramy krok którego błąd dotyczy
const bledy = @json($errors->keys());
kreator.otworz(
    bledy.some(k => k.startsWith('uslugi')) ? 1
    : bledy.some(k => ['data', 'godzina', 'id_mechanika'].includes(k)) ? 2
    : bledy.some(k => ['pojazd_id', 'uwagi'].includes(k)) ? 3
    : 1
);
odswiezPodsumowanie();
</script>

@endif
@endsection