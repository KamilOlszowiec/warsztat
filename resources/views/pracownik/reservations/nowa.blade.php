@extends(auth()->user()->czyAdministrator() ? 'layouts.admin' : 'layouts.pracownik')
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

<x-kroki :nazwy="['Usługi', 'Termin', 'Klient', 'Pojazd', 'Zapis']" />

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

            @error('uslugi') <p class="text-sm text-red-500 mb-3">{{ $message }}</p> @enderror

            @unless ($czyAdmin)
                <p class="text-xs font-mono text-zinc-400 mb-2">TWOJE USŁUGI</p>
            @endunless

            @if ($mojeUslugi->isEmpty())
                <p class="text-sm text-zinc-500 bg-[#1F2128] border border-zinc-800 px-4 py-3" style="border-radius: 2px;">
                    Nie masz przypisanych specjalizacji, więc nie możesz przyjąć wizyty na siebie. Skontaktuj się z administratorem.
                </p>
            @endif

            <div class="space-y-1.5">
                @foreach ($mojeUslugi as $u)
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

            {{-- Usługi, których pracownik nie wykona – tylko do wglądu --}}
            @if ($inneUslugi->isNotEmpty())
                <details class="mt-5 group">
                    <summary class="cursor-pointer select-none text-sm text-zinc-400 hover:text-zinc-200 list-none [&::-webkit-details-marker]:hidden">
                        <span class="inline-block transition group-open:rotate-90">›</span>
                        Usługi innych mechaników ({{ $inneUslugi->count() }})
                    </summary>
                    <p class="text-xs text-zinc-500 mt-2 mb-2">
                        Nie masz specjalizacji wymaganej do tych usług. Wizytę może przyjąć mechanik z odpowiednią specjalizacją albo administrator.
                    </p>
                    <div class="space-y-1.5">
                        @foreach ($inneUslugi as $u)
                            <div class="flex items-center justify-between gap-4 px-4 py-2.5 bg-[#1F2128]/50 border border-zinc-800/60 opacity-60 cursor-not-allowed" style="border-radius: 2px;" aria-disabled="true">
                                <p class="text-sm text-zinc-300">
                                    {{ $u->nazwa }}
                                    <span class="ml-2 text-xs font-mono text-zinc-500">wymaga: {{ $u->specjalizacja?->nazwa ?? '—' }}</span>
                                </p>
                                <p class="text-sm text-zinc-400 whitespace-nowrap">{{ number_format($u->cena, 2) }} zł</p>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
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
                <x-kalendarz id="kalendarz" />
                <x-siatka-godzin id="godziny" />
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

@vite('resources/js/rezerwacje.js')
<script type="module">
const { format, kalendarz, siatkaGodzin, listaMechanikow, kroki } = window.Rezerwacje;

const form = document.getElementById('formularz');
const checkboxy = Array.from(form.querySelectorAll('input[name="uslugi[]"]'));
const poleData = document.getElementById('pole-data');
const poleMechanik = document.getElementById('pole-mechanik'); // tylko administrator
const poleSzukaj = document.getElementById('pole-szukaj');
const poleIdKlienta = document.getElementById('pole-id-klienta');
const listaKlientow = document.getElementById('klienci');
const komunikatKlientow = document.getElementById('klienci-komunikat');
const polePojazdTryb = document.getElementById('pole-pojazd-tryb');
const pojazdyKlienta = document.getElementById('pojazdy-klienta');
const nowyPojazd = document.getElementById('nowy-pojazd');

let wybranyKlient = @json($wybranyKlient);   // {id, nazwa, telefon, email, pojazdy}
let poprzedniPojazd = @json(old('id_pojazdu'));
let numerKlientow = 0, timerSzukania = null;

const zaznaczone = () => checkboxy.filter(c => c.checked);
const lacznyCzas = () => zaznaczone().reduce((s, c) => s + Number(c.dataset.czas), 0);
const lacznaCena = () => zaznaczone().reduce((s, c) => s + parseFloat(c.dataset.cena), 0);
const trybKlienta = () => form.querySelector('input[name="klient_tryb"]:checked').value;
const pole = nazwa => form.querySelector('[name="' + nazwa + '"]');
const wypelnione = (...nazwy) => nazwy.every(n => pole(n) && pole(n).value.trim() !== '');

function paramsUslug() {
    const p = new URLSearchParams();
    zaznaczone().forEach(c => p.append('uslugi[]', c.value));
    return p;
}

// ---------- Elementy wspólne ----------

const mechanicy = poleMechanik ? listaMechanikow(poleMechanik, {
    url: @json(route('pracownik.reservations.nowa.mechanicy')),
    parametry: () => (zaznaczone().length ? paramsUslug() : null),
    poZmianieWyboru: () => (poleData.value ? godziny.pobierz() : odswiez()),
    poczatkowy: @json(old('id_mechanika')),
}) : null;

const godziny = siatkaGodzin(document.getElementById('godziny'), {
    url: @json(route('pracownik.reservations.nowa.terminy')),
    zaznacz: @json(old('godzina')),
    opis: () => (poleData.value ? format.data(poleData.value) + ' · łączny czas: ' + format.czas(lacznyCzas()) : ''),
    parametry: () => {
        if (!poleData.value) return 'Wybierz dzień w kalendarzu.';
        if (!zaznaczone().length) return 'Najpierw wybierz usługi w kroku 1.';
        const p = paramsUslug();
        p.append('data', poleData.value);
        if (poleMechanik?.value) p.append('mechanik', poleMechanik.value);
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
    liczba: 5,
    gotowy: n => {
        if (n === 1) return zaznaczone().length > 0;
        if (n === 2) return !!poleData.value && !!godziny.wybrana();
        if (n === 3) return trybKlienta() === 'nowy' ? wypelnione('imie', 'nazwisko', 'telefon') : !!poleIdKlienta.value;
        if (n === 4) return polePojazdTryb.value === 'nowy'
            ? wypelnione('marka', 'model', 'numer_rejestracyjny')
            : !!form.querySelector('input[name="id_pojazdu"]:checked');
        return true;
    },
    poWejsciu: n => {
        if (n === 2) mechanicy?.pobierz();
        if (n === 4) renderujPojazdy();
        if (n === 5) renderujPotwierdzenie();
        odswiezPodsumowanie();
    },
    wstecz: document.getElementById('btn-wstecz'),
    dalej: document.getElementById('btn-dalej'),
    zatwierdz: document.getElementById('btn-zapisz'),
    tekstWysylania: 'Zapisywanie…',
});

// ---------- Opisy do podsumowania ----------

const opisMechanika = () => (mechanicy ? mechanicy.opis() : 'Ty');

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
    return form.querySelector('input[name="id_pojazdu"]:checked')?.dataset.opis ?? '—';
}

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
        const w = document.createElement('div');
        w.className = 'flex justify-between gap-3';
        const nazwa = document.createElement('span');
        nazwa.className = 'text-zinc-300';
        nazwa.textContent = c.dataset.nazwa;
        const czas = document.createElement('span');
        czas.className = 'text-xs font-mono text-zinc-500 whitespace-nowrap';
        czas.textContent = format.czas(Number(c.dataset.czas));
        w.append(nazwa, czas);
        lista.appendChild(w);
    });

    document.getElementById('pods-czas').textContent = wybrane.length ? format.czas(lacznyCzas()) : '—';
    document.getElementById('pods-cena').textContent = wybrane.length ? format.kwota(lacznaCena()) : '—';

    const g = godziny.wybrana();
    document.getElementById('pods-termin').textContent = poleData.value ? format.data(poleData.value) + (g ? ', ' + g : '') : '—';
    document.getElementById('pods-mechanik').textContent = opisMechanika();
    document.getElementById('pods-klient').textContent = opisKlienta();
    document.getElementById('pods-pojazd').textContent = opisPojazdu();
}

function odswiez() {
    odswiezPodsumowanie();
    kreator.odswiez();
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
    else if (wybranyKlient?.pojazdy.length) polePojazdTryb.value = 'istniejacy';
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
            const odp = await fetch(@json(route('pracownik.reservations.nowa.klienci')) + '?q=' + encodeURIComponent(fraza), { headers: { Accept: 'application/json' } });
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
    if (!pojazdy.length) polePojazdTryb.value = 'nowy';

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
    const g = godziny.wybrana();

    const kontakt = trybKlienta() === 'nowy'
        ? pole('telefon').value.trim() + '\n' + (pole('email').value.trim() || 'bez e-maila – brak potwierdzenia')
        : [wybranyKlient.telefon, wybranyKlient.email || 'bez e-maila – brak potwierdzenia'].filter(Boolean).join('\n');

    kontener.append(
        wiersz('Usługi', zaznaczone().map(c => c.dataset.nazwa + ' – ' + format.kwota(parseFloat(c.dataset.cena))).join('\n')),
        wiersz('Termin', format.data(poleData.value) + '\n' + g + '–' + format.dodajMinuty(g, lacznyCzas())),
        wiersz('Mechanik', opisMechanika()),
        wiersz('Klient', opisKlienta()),
        wiersz('Kontakt', kontakt),
        wiersz('Pojazd', opisPojazdu() + (polePojazdTryb.value === 'nowy' ? ' – nowy' : '')),
        wiersz('Szacowana kwota', format.kwota(lacznaCena()), true)
    );
}

// ---------- Zdarzenia i start ----------

checkboxy.forEach(c => c.addEventListener('change', () => {
    mechanicy?.pobierz();
    if (poleData.value) godziny.pobierz(); else odswiez();
}));
form.querySelectorAll('input[name="klient_tryb"]').forEach(r => r.addEventListener('change', przelaczTrybKlienta));
poleSzukaj.addEventListener('input', szukajKlientow);
form.addEventListener('input', e => { if (e.target.closest('[data-tryb-klienta="nowy"], #nowy-pojazd')) odswiez(); });

przelaczTrybKlienta();
if (wybranyKlient) {
    poleIdKlienta.value = wybranyKlient.id;
    renderujKlientow([wybranyKlient]);
    komunikatKlientow.classList.add('hidden');
}
if (poleData.value && zaznaczone().length) godziny.pobierz();
if (zaznaczone().length) mechanicy?.pobierz();

// Po błędzie walidacji otwieramy krok, którego dotyczy błąd (o ile poprzednie są kompletne)
const bledy = @json($errors->keys());
const krokBledu = k => k.startsWith('uslugi') ? 1
    : ['data', 'godzina', 'id_mechanika'].includes(k) ? 2
    : ['klient_tryb', 'id_klienta', 'imie', 'nazwisko', 'telefon', 'email'].includes(k) ? 3
    : ['pojazd_tryb', 'id_pojazdu', 'marka', 'model', 'numer_rejestracyjny', 'rok_produkcji', 'vin'].includes(k) ? 4 : 5;
kreator.otworz(bledy.length ? Math.min(...bledy.map(krokBledu)) : 1);
</script>
@endsection
