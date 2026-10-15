@extends('layouts.admin')
@section('title', 'Grafik mechaników')

@section('content')
@php
    $skroty = ['PN', 'WT', 'ŚR', 'CZ', 'PT', 'SO', 'ND'];
@endphp

<div class="max-w-6xl space-y-4">

    {{-- Nagłówek --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">ZESPÓŁ</p>
            <h1 class="text-2xl font-bold text-zinc-50">Grafik mechaników</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.shifts.copy') }}"
                  onsubmit="return confirm('Zastąpić grafik tego tygodnia kopią poprzedniego tygodnia?');">
                @csrf
                <input type="hidden" name="tydzien" value="{{ $poczatek->toDateString() }}">
                <button type="submit" class="min-h-[40px] px-3.5 border border-zinc-700 text-zinc-300 hover:text-white hover:border-zinc-500 text-[13px] font-medium transition" style="border-radius: 2px;">
                    Kopiuj poprzedni tydzień
                </button>
            </form>
            <form method="POST" action="{{ route('admin.shifts.fill') }}"
                  onsubmit="return confirm('Ustawić wszystkim mechanikom zmiany zgodne z godzinami pracy? Obecny grafik tego tygodnia zostanie nadpisany.');">
                @csrf
                <input type="hidden" name="tydzien" value="{{ $poczatek->toDateString() }}">
                <button type="submit" class="min-h-[40px] px-3.5 border border-orange-600 text-orange-400 hover:bg-orange-600 hover:text-white text-[13px] font-medium transition" style="border-radius: 2px;">
                    Wypełnij wg godzin pracy
                </button>
            </form>
        </div>
    </div>

    {{-- Nawigacja tygodni --}}
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.shifts.index', ['tydzien' => $poczatek->copy()->subWeek()->toDateString()]) }}" aria-label="Poprzedni tydzień"
           class="w-10 h-10 flex items-center justify-center bg-[#1F2128] border border-zinc-700 text-zinc-300 hover:text-white text-lg" style="border-radius: 2px;">‹</a>
        <p class="min-w-[200px] text-center font-semibold text-zinc-50">
            {{ $poczatek->format('d.m') }} – {{ $koniec->translatedFormat('d.m.Y') }}
        </p>
        <a href="{{ route('admin.shifts.index', ['tydzien' => $poczatek->copy()->addWeek()->toDateString()]) }}" aria-label="Następny tydzień"
           class="w-10 h-10 flex items-center justify-center bg-[#1F2128] border border-zinc-700 text-zinc-300 hover:text-white text-lg" style="border-radius: 2px;">›</a>
        @unless ($poczatek->isSameDay(today()->startOfWeek()))
            <a href="{{ route('admin.shifts.index') }}" class="h-10 inline-flex items-center px-3.5 border border-zinc-700 text-zinc-400 hover:text-zinc-200 text-sm" style="border-radius: 2px;">Bieżący tydzień</a>
        @endunless
        <span class="ml-2 text-[13px] text-zinc-400">Kliknij komórkę, żeby zmienić zmianę.</span>
    </div>

    @error('grafik')
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    @if ($pracownicy->isEmpty())
        <div class="bg-[#1F2128] border border-zinc-800 p-8 text-center text-zinc-400" style="border-radius: 2px;">
            Brak mechaników. Dodaj pracownika w zakładce <a href="{{ route('admin.users.index') }}" class="text-orange-500 hover:text-orange-400">Użytkownicy</a>.
        </div>
    @else
    <form id="form-grafiku" method="POST" action="{{ route('admin.shifts.save') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="tydzien" value="{{ $poczatek->toDateString() }}">

        {{-- Tabela grafiku --}}
        <div class="bg-[#1F2128] border border-zinc-800 overflow-x-auto" style="border-radius: 2px;">
            <table class="w-full min-w-[900px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-zinc-800">
                        <th class="w-[190px]"></th>
                        @foreach ($dni as $dzien)
                            @php $godziny = $godzinyPracy->get($dzien->dayOfWeekIso); @endphp
                            <th class="px-2 py-2.5 border-l border-zinc-800 text-center font-normal">
                                <p class="text-xs font-mono font-semibold {{ $dzien->isToday() ? 'text-orange-400' : 'text-zinc-200' }}">
                                    {{ $skroty[$dzien->dayOfWeekIso - 1] }} {{ $dzien->format('d.m') }}
                                </p>
                                <p class="text-[10px] font-mono text-zinc-500 mt-0.5">
                                    @if ($dniWolne->has($dzien->toDateString()))
                                        dzień wolny
                                    @elseif ($godziny && $godziny->czy_czynne)
                                        {{ substr($godziny->godzina_otwarcia, 0, 5) }}–{{ substr($godziny->godzina_zamkniecia, 0, 5) }}
                                    @else
                                        nieczynne
                                    @endif
                                </p>
                            </th>
                        @endforeach
                        <th class="w-[70px] px-3 py-2.5 border-l border-zinc-800 text-right text-[11px] font-mono font-normal text-zinc-400">SUMA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pracownicy as $p)
                        <tr class="border-b border-zinc-800">
                            <td class="px-3.5 py-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-[30px] h-[30px] shrink-0 flex items-center justify-center bg-zinc-700 text-zinc-50 text-[11px] font-bold" style="border-radius: 50%;">
                                        {{ mb_strtoupper(mb_substr($p->imie, 0, 1) . mb_substr($p->nazwisko, 0, 1), 'UTF-8') }}
                                    </span>
                                    <span class="text-[13px] font-semibold text-zinc-50 truncate">{{ $p->imie }} {{ $p->nazwisko }}</span>
                                </div>
                            </td>

                            @foreach ($dni as $dzien)
                                @php
                                    $data = $dzien->toDateString();
                                    $godziny = $godzinyPracy->get($dzien->dayOfWeekIso);
                                    $zmiana = $zmiany->get($p->id_uzytkownika . '_' . $data);
                                    $startOd = $zmiana ? substr($zmiana->godzina_od, 0, 5) : '';
                                    $startDo = $zmiana ? substr($zmiana->godzina_do, 0, 5) : '';
                                    $klucz = "zmiany.{$p->id_uzytkownika}.{$data}";
                                @endphp
                                <td class="p-1.5 border-l border-zinc-800 align-middle">
                                    @if ($dniWolne->has($data))
                                        <div class="min-h-[52px] flex items-center justify-center text-[11px] text-zinc-500 border border-dashed border-zinc-800" style="border-radius: 2px;">dzień wolny</div>
                                    @elseif (!$godziny || !$godziny->czy_czynne)
                                        <div class="min-h-[52px] flex items-center justify-center text-[11px] text-zinc-600 border border-dashed border-zinc-800" style="border-radius: 2px;">nieczynne</div>
                                    @else
                                        <button type="button" class="komorka relative w-full min-h-[52px] px-2 py-1.5 flex flex-col justify-center gap-1.5 text-left transition"
                                                data-pracownik="{{ $p->id_uzytkownika }}"
                                                data-nazwa="{{ $p->imie }} {{ $p->nazwisko }}"
                                                data-data="{{ $data }}"
                                                data-etykieta="{{ $dzien->translatedFormat('l, d.m') }}"
                                                data-otwarcie="{{ substr($godziny->godzina_otwarcia, 0, 5) }}"
                                                data-zamkniecie="{{ substr($godziny->godzina_zamkniecia, 0, 5) }}"
                                                data-start-od="{{ $startOd }}"
                                                data-start-do="{{ $startDo }}"
                                                style="border-radius: 2px;">
                                            <span class="kropka hidden absolute top-1 right-1 w-1.5 h-1.5 bg-orange-500" style="border-radius: 50%;"></span>
                                            <span class="tekst text-[11px] font-mono"></span>
                                            <span class="tor relative block h-1 bg-white/5" style="border-radius: 2px;">
                                                <span class="pasek absolute inset-y-0 bg-green-400" style="border-radius: 2px;"></span>
                                            </span>
                                        </button>
                                        <input type="hidden" class="pole-od" name="zmiany[{{ $p->id_uzytkownika }}][{{ $data }}][od]" value="{{ old($klucz . '.od', $startOd) }}">
                                        <input type="hidden" class="pole-do" name="zmiany[{{ $p->id_uzytkownika }}][{{ $data }}][do]" value="{{ old($klucz . '.do', $startDo) }}">
                                    @endif
                                </td>
                            @endforeach

                            <td class="px-3 border-l border-zinc-800 text-right font-mono text-[13px] text-zinc-200">
                                <span class="suma" data-pracownik="{{ $p->id_uzytkownika }}">0 h</span>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Obsada --}}
                    <tr class="bg-[#18191D]">
                        <td class="px-3.5 py-2.5 text-[11px] font-mono text-zinc-400">OBSADA</td>
                        @foreach ($dni as $dzien)
                            @php
                                $godziny = $godzinyPracy->get($dzien->dayOfWeekIso);
                                $otwarte = $godziny && $godziny->czy_czynne && !$dniWolne->has($dzien->toDateString());
                            @endphp
                            <td class="px-1.5 py-2.5 border-l border-zinc-800 text-center">
                                @if ($otwarte)
                                    <span class="obsada text-xs" data-data="{{ $dzien->toDateString() }}"></span>
                                @else
                                    <span class="text-xs text-zinc-600">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="border-l border-zinc-800"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Panel edycji wybranej komórki --}}
        <section id="panel-edycji" class="bg-[#1F2128] border border-zinc-800 px-5 py-4" style="border-radius: 2px;" aria-live="polite">
            <p id="panel-pusty" class="text-sm text-zinc-400">Wybierz komórkę w grafiku, żeby ustawić zmianę mechanika.</p>

            <div id="panel-tresc" class="hidden flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[220px]">
                    <p class="text-[11px] font-mono text-orange-400">EDYCJA ZMIANY</p>
                    <p id="panel-kto" class="text-[15px] font-semibold text-zinc-50 mt-1"></p>
                    <p class="text-[13px] text-zinc-400 mt-0.5"><span id="panel-kiedy"></span> · teraz: <span id="panel-obecnie" class="text-zinc-100"></span></p>
                </div>
                <div id="panel-szablony" class="flex flex-wrap gap-2"></div>
                <div class="flex items-center gap-2">
                    <label class="sr-only" for="panel-od">Od</label>
                    <select id="panel-od" class="h-[44px] px-2 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm font-mono focus:outline-none focus:border-orange-600" style="border-radius: 2px;"></select>
                    <span class="text-zinc-500">–</span>
                    <label class="sr-only" for="panel-do">Do</label>
                    <select id="panel-do" class="h-[44px] px-2 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm font-mono focus:outline-none focus:border-orange-600" style="border-radius: 2px;"></select>
                </div>
            </div>
        </section>

        {{-- Zapis --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p id="licznik-zmian" class="text-[13px] text-zinc-400">Brak niezapisanych zmian.</p>
            <button type="submit" class="min-h-[44px] px-6 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
                Zapisz grafik
            </button>
        </div>
    </form>
    @endif
</div>

@if ($pracownicy->isNotEmpty())
<script>
(function () {
    const komorki = Array.from(document.querySelectorAll('.komorka'));
    const naMin = (t) => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    const naTekst = (m) => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
    let wybrana = null;
    let wysylanie = false;
    const pola = (k) => {
        const td = k.closest('td');
        return { od: td.querySelector('.pole-od'), do: td.querySelector('.pole-do') };
    };

    function zmieniona(k) {
        const p = pola(k);
        return p.od.value !== k.dataset.startOd || p.do.value !== k.dataset.startDo;
    }

    function rysuj(k) {
        const p = pola(k);
        const maZmiane = p.od.value && p.do.value;
        const tekst = k.querySelector('.tekst');
        const pasek = k.querySelector('.pasek');
        const otw = naMin(k.dataset.otwarcie), zam = naMin(k.dataset.zamkniecie);       

        let tlo = maZmiane
            ? 'bg-green-900/30 border border-green-500/35'
            : 'border border-zinc-800 bg-[repeating-linear-gradient(135deg,#18191D_0_5px,#202127_5px_10px)]';
        if (k === wybrana) tlo += ' outline outline-2 outline-orange-500 outline-offset-1';

        k.className = 'komorka relative w-full min-h-[52px] px-2 py-1.5 flex flex-col justify-center gap-1.5 text-left transition hover:brightness-125 ' + tlo;

        if (maZmiane) {
            tekst.textContent = p.od.value + '–' + p.do.value;
            tekst.className = 'tekst text-[11px] font-mono font-semibold text-zinc-50';
            k.querySelector('.tor').classList.remove('hidden');
            pasek.style.left = ((naMin(p.od.value) - otw) / (zam - otw) * 100) + '%';
            pasek.style.width = ((naMin(p.do.value) - naMin(p.od.value)) / (zam - otw) * 100) + '%';
        } else {
            tekst.textContent = 'wolne';
            tekst.className = 'tekst text-[11px] font-mono text-zinc-500';
            k.querySelector('.tor').classList.add('hidden');
        }

        k.querySelector('.kropka').classList.toggle('hidden', !zmieniona(k));
        k.setAttribute('aria-label', k.dataset.nazwa + ', ' + k.dataset.etykieta + ': ' + (maZmiane ? p.od.value + ' do ' + p.do.value : 'wolne'));
    }

    function podsumowania() {
        // Suma godzin każdego mechanika
        document.querySelectorAll('.suma').forEach(s => {
            let min = 0;
            komorki.filter(k => k.dataset.pracownik === s.dataset.pracownik).forEach(k => {
                const p = pola(k);
                if (p.od.value && p.do.value) min += naMin(p.do.value) - naMin(p.od.value);
            });
            const h = min / 60;
            s.textContent = (Number.isInteger(h) ? h : h.toFixed(1).replace('.', ',')) + ' h';
        });

        // Obsada każdego dnia
        document.querySelectorAll('.obsada').forEach(o => {
            const n = komorki.filter(k => k.dataset.data === o.dataset.data && pola(k).od.value).length;
            if (n === 0) {
                o.textContent = 'nikt';
                o.className = 'obsada text-[11px] font-semibold px-2 py-0.5 bg-red-900/50 text-red-300';
            } else if (n === 1) {
                o.textContent = '1 osoba';
                o.className = 'obsada text-[11px] font-semibold px-2 py-0.5 bg-yellow-900/50 text-yellow-200';
            } else {
                o.textContent = n + (n < 5 ? ' osoby' : ' osób');
                o.className = 'obsada text-xs text-zinc-300';
            }
            o.style.borderRadius = '2px';
        });

        const liczba = komorki.filter(zmieniona).length;
        const licznik = document.getElementById('licznik-zmian');
        licznik.textContent = liczba === 0 ? 'Brak niezapisanych zmian.' : 'Niezapisane zmiany: ' + liczba + ' – oznaczone pomarańczową kropką.';
        licznik.className = 'text-[13px] ' + (liczba ? 'text-orange-400' : 'text-zinc-400');
    }

    function ustaw(k, od, doG) {
        const p = pola(k);
        p.od.value = od || '';
        p.do.value = doG || '';
        rysuj(k);
        podsumowania();
        panel();
    }

    function wypelnijListe(select, od, doM, wartosc) {
        select.innerHTML = '';
        for (let m = od; m <= doM; m += 30) {
            const o = document.createElement('option');
            o.value = o.textContent = naTekst(m);
            if (o.value === wartosc) o.selected = true;
            select.appendChild(o);
        }
    }

    function panel() {
        if (!wybrana) return;
        document.getElementById('panel-pusty').classList.add('hidden');
        document.getElementById('panel-tresc').classList.remove('hidden');

        const p = pola(wybrana);
        const otw = naMin(wybrana.dataset.otwarcie), zam = naMin(wybrana.dataset.zamkniecie);
        const obecnie = p.od.value ? p.od.value + '–' + p.do.value : 'wolne';

        document.getElementById('panel-kto').textContent = wybrana.dataset.nazwa;
        document.getElementById('panel-kiedy').textContent = wybrana.dataset.etykieta;
        document.getElementById('panel-obecnie').textContent = obecnie;

        // Szablony liczone z godzin otwarcia danego dnia
        const szablony = [
            ['Cały dzień', otw, zam],
            ['Rano', otw, Math.min(otw + 240, zam)],
            ['Popołudnie', Math.max(zam - 240, otw), zam],
            ['Wolne', null, null],
        ];
        const kontener = document.getElementById('panel-szablony');
        kontener.innerHTML = '';
        // Pomijamy szablony, które dają te same godziny co wcześniejszy (np. w krótkim dniu)
        const widziane = new Set();
        szablony.filter(([, od, doM]) => {
            const klucz = od === null ? 'wolne' : od + '-' + doM;
            if (widziane.has(klucz)) return false;
            widziane.add(klucz);
            return true;
        }).forEach(([nazwa, od, doM]) => {
            const aktywny = od === null ? !p.od.value : (p.od.value === naTekst(od) && p.do.value === naTekst(doM));
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'min-w-[104px] min-h-[44px] px-3 py-1.5 text-left border transition '
                + (aktywny ? 'bg-orange-600/15 border-orange-600' : 'border-zinc-700 hover:border-zinc-500');
            b.style.borderRadius = '2px';
            b.innerHTML = '<span class="block text-[13px] font-semibold text-zinc-50"></span><span class="block text-[10px] font-mono text-zinc-400"></span>';
            b.children[0].textContent = nazwa;
            b.children[1].textContent = od === null ? 'urlop / dzień wolny' : naTekst(od) + '–' + naTekst(doM);
            b.addEventListener('click', () => ustaw(wybrana, od === null ? '' : naTekst(od), od === null ? '' : naTekst(doM)));
            kontener.appendChild(b);
        });

        // Własne godziny co 30 minut w zakresie otwarcia
        const selOd = document.getElementById('panel-od');
        const selDo = document.getElementById('panel-do');
        wypelnijListe(selOd, otw, zam - 30, p.od.value || naTekst(otw));
        wypelnijListe(selDo, otw + 30, zam, p.do.value || naTekst(zam));
    }

    function wybierz(k) {
        const poprzednia = wybrana;
        wybrana = k;
        if (poprzednia) rysuj(poprzednia);
        rysuj(k);
        panel();
    }

    // Własne godziny z list
    document.getElementById('panel-od').addEventListener('change', function () {
        if (!wybrana) return;
        let doG = document.getElementById('panel-do').value;
        if (naMin(doG) <= naMin(this.value)) doG = naTekst(naMin(this.value) + 30);
        ustaw(wybrana, this.value, doG);
    });
    document.getElementById('panel-do').addEventListener('change', function () {
        if (!wybrana) return;
        let od = document.getElementById('panel-od').value;
        if (naMin(this.value) <= naMin(od)) od = naTekst(naMin(this.value) - 30);
        ustaw(wybrana, od, this.value);
    });

    komorki.forEach(k => k.addEventListener('click', () => wybierz(k)));

    // Ostrzeżenie przy wyjściu ze strony z niezapisanymi zmianami
    document.getElementById('form-grafiku').addEventListener('submit', () => { wysylanie = true; });
    window.addEventListener('beforeunload', (e) => {
        if (!wysylanie && komorki.some(zmieniona)) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    komorki.forEach(rysuj);
    podsumowania();
})();
</script>
@endif
@endsection