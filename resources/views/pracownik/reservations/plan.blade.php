@extends('layouts.pracownik')
@section('title', 'Plan dnia')

@section('content')
@php
    $etykiety = [
        'oczekująca'   => 'bg-yellow-900/40 text-yellow-400',
        'zatwierdzona' => 'bg-green-900/40 text-green-400',
        'zakończona'   => 'bg-blue-900/40 text-blue-400',
    ];
    $bloki = [
        'oczekująca'   => 'bg-yellow-900/40 border border-dashed border-yellow-400',
        'zatwierdzona' => 'bg-green-900/50 border-l-[3px] border-green-400',
        'zakończona'   => 'bg-blue-900/40 border-l-[3px] border-blue-400',
    ];
    $tloSiatki = 'background-image: repeating-linear-gradient(to bottom, #2C2D33 0 1px, transparent 1px 64px), repeating-linear-gradient(to bottom, #232429 0 1px, transparent 1px 32px);';
    $kreskowanie = 'background: repeating-linear-gradient(135deg, rgba(24,25,29,0.9) 0 6px, rgba(36,37,43,0.9) 6px 12px);';
    $liczbaKolumn = count($kolumny);
    $siatka = 'grid-template-columns: 56px repeat(' . max($liczbaKolumn, 1) . ', minmax(150px, 1fr));';
    $url = fn ($r) => route('pracownik.reservations.plan', ['data' => $dzien->toDateString(), 'wizyta' => $r->id_rezerwacji]);
@endphp

<div class="max-w-7xl">
    @include('pracownik.reservations._zakladki', ['aktywna' => 'plan', 'tytul' => 'Plan dnia'])

    @error('error')
        <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    {{-- Nawigacja po dniach i legenda --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('pracownik.reservations.plan', ['data' => $dzien->copy()->subDay()->toDateString()]) }}" aria-label="Poprzedni dzień"
               class="w-10 h-10 flex items-center justify-center bg-[#1F2128] border border-zinc-700 text-zinc-300 hover:text-white text-lg" style="border-radius: 2px;">‹</a>
            <p class="min-w-[230px] text-center font-semibold text-zinc-50">{{ $dzien->translatedFormat('l, j F Y') }}</p>
            <a href="{{ route('pracownik.reservations.plan', ['data' => $dzien->copy()->addDay()->toDateString()]) }}" aria-label="Następny dzień"
               class="w-10 h-10 flex items-center justify-center bg-[#1F2128] border border-zinc-700 text-zinc-300 hover:text-white text-lg" style="border-radius: 2px;">›</a>
            @unless ($dzien->isToday())
                <a href="{{ route('pracownik.reservations.plan') }}"
                   class="h-10 inline-flex items-center px-3.5 border border-zinc-700 text-zinc-400 hover:text-zinc-200 text-sm" style="border-radius: 2px;">Dziś</a>
            @endunless
            <form method="GET" action="{{ route('pracownik.reservations.plan') }}">
                <label for="data-planu" class="sr-only">Przejdź do dnia</label>
                <input id="data-planu" type="date" name="data" value="{{ $dzien->toDateString() }}" onchange="this.form.submit()"
                       class="h-10 px-2 bg-[#1F2128] border border-zinc-700 text-zinc-300 text-sm [color-scheme:dark]" style="border-radius: 2px;">
            </form>
        </div>
        <div class="flex flex-wrap gap-4 text-xs text-zinc-400">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-yellow-900/50 border border-dashed border-yellow-400"></span>Oczekująca</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-green-900/60 border-l-[3px] border-green-400"></span>Zatwierdzona</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-blue-900/50 border-l-[3px] border-blue-400"></span>Zakończona</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 border border-zinc-700" style="{{ $kreskowanie }}"></span>Poza zmianą</span>
        </div>
    </div>

    @if ($dzienWolny)
        <div class="mb-4 bg-zinc-800/60 border border-zinc-700 text-zinc-300 px-4 py-3 text-sm" style="border-radius: 2px;">
            Dzień wolny warsztatu{{ $dzienWolny->opis ? ' – ' . $dzienWolny->opis : '' }}.
        </div>
    @elseif (!$otwarteWTenDzien)
        <div class="mb-4 bg-zinc-800/60 border border-zinc-700 text-zinc-300 px-4 py-3 text-sm" style="border-radius: 2px;">
            Warsztat jest nieczynny w ten dzień tygodnia.
        </div>
    @endif

    <div class="flex flex-wrap gap-5 items-start">

        {{-- Plan --}}
        <section aria-label="Plan dnia mechaników" class="flex-[1_1_560px] min-w-0 bg-[#1F2128] border border-zinc-800 overflow-x-auto" style="border-radius: 2px;">
            @if ($liczbaKolumn === 0)
                <p class="p-8 text-center text-sm text-zinc-400">Brak pracowników w systemie.</p>
            @else
                <div style="min-width: {{ 56 + $liczbaKolumn * 150 }}px;">
                    {{-- Nagłówki kolumn --}}
                    <div class="grid border-b border-zinc-800" style="{{ $siatka }}">
                        <div></div>
                        @foreach ($kolumny as $k)
                            <div class="flex items-center gap-2.5 p-3 border-l border-zinc-800 min-w-0">
                                <div class="w-8 h-8 shrink-0 flex items-center justify-center bg-zinc-700 text-zinc-50 text-xs font-bold" style="border-radius: 50%;">{{ $k['inicjaly'] }}</div>
                                <div class="min-w-0">
                                    <p class="text-[13px] font-semibold text-zinc-50 truncate">{{ $k['tytul'] }}</p>
                                    <p class="text-[11px] font-mono text-zinc-400">{{ $k['podtytul'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Oś czasu --}}
                    <div class="grid py-3" style="{{ $siatka }}">
                        <div class="relative" style="height: {{ $wysokosc }}px;">
                            @foreach ($etykietyGodzin as $g)
                                <span class="absolute right-2 text-[11px] font-mono text-zinc-500" style="top: {{ $g['top'] - 7 }}px;">{{ $g['etykieta'] }}</span>
                            @endforeach
                            @if ($teraz !== null)
                                <span class="absolute right-1 px-1 text-[10px] font-mono font-bold bg-red-600 text-white" style="top: {{ $teraz - 8 }}px; border-radius: 2px;">{{ now()->format('H:i') }}</span>
                            @endif
                        </div>

                        @foreach ($kolumny as $k)
                            <div class="relative border-l border-zinc-800" style="height: {{ $wysokosc }}px; {{ $tloSiatki }}">
                                @foreach ($k['poza'] as $p)
                                    <div class="absolute inset-x-0 flex items-center justify-center" style="top: {{ $p['top'] }}px; height: {{ $p['wysokosc'] }}px; {{ $kreskowanie }}">
                                        <span class="text-[11px] text-zinc-500">{{ $p['etykieta'] }}</span>
                                    </div>
                                @endforeach

                                @foreach ($k['wizyty'] as $w)
                                    @php
                                        $r = $w['rezerwacja'];
                                        $zaznaczona = $wybrana && $wybrana->id_rezerwacji === $r->id_rezerwacji;
                                    @endphp
                                    @php $krotka = $w['wysokosc'] < 50; @endphp
                                    <a href="{{ $url($r) }}" @if ($zaznaczona) aria-current="true" @endif
                                       class="absolute left-1 right-1 overflow-hidden {{ $krotka ? 'flex items-center gap-1.5 px-2' : 'px-2 py-1' }} {{ $bloki[$r->status] ?? 'bg-zinc-800' }} {{ $zaznaczona ? 'ring-2 ring-orange-500 ring-offset-1 ring-offset-[#1F2128]' : 'hover:brightness-125' }}"
                                       style="top: {{ $w['top'] + 1 }}px; height: {{ max($w['wysokosc'] - 3, 18) }}px; border-radius: 2px;"
                                       title="{{ $r->termin_rozpoczecia->format('H:i') }}–{{ $r->termin_zakonczenia->format('H:i') }} · {{ $r->nazwy_uslug }} – {{ $r->klient->imie }} {{ $r->klient->nazwisko }}">
                                        @if ($krotka)
                                            {{-- Krótka wizyta: godzina i usługa w jednej linii --}}
                                            <span class="shrink-0 text-[10px] font-mono text-zinc-300">{{ $r->termin_rozpoczecia->format('H:i') }}</span>
                                            <span class="min-w-0 text-xs font-semibold text-zinc-50 truncate">{{ $r->nazwy_uslug }}</span>
                                        @else
                                            <span class="block text-[10px] font-mono text-zinc-300">{{ $r->termin_rozpoczecia->format('H:i') }}–{{ $r->termin_zakonczenia->format('H:i') }}</span>
                                            <span class="block text-xs font-semibold text-zinc-50 truncate">{{ $r->nazwy_uslug }}</span>
                                            <span class="block text-[11px] text-zinc-300 truncate">{{ $r->klient->imie }} {{ $r->klient->nazwisko }}</span>
                                        @endif
                                    </a>
                                @endforeach

                                @if ($teraz !== null)
                                    <div class="absolute inset-x-0 h-0.5 bg-red-600 pointer-events-none" style="top: {{ $teraz }}px;"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Panel wybranej wizyty --}}
        <aside aria-label="Szczegóły wizyty" class="w-full lg:w-[300px] shrink-0 bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
            @if ($wybrana)
                @php
                    $r = $wybrana;
                    $przyszla = $r->termin_rozpoczecia->isFuture();
                @endphp
                <div class="px-5 py-4 border-b border-zinc-800">
                    <span class="text-[11px] font-mono px-2 py-0.5 {{ $etykiety[$r->status] ?? 'bg-zinc-800 text-zinc-400' }}" style="border-radius: 2px;">
                        {{ mb_strtoupper($r->status, 'UTF-8') }}
                    </span>
                    <h2 class="text-[17px] font-bold text-zinc-50 mt-2.5 mb-1">{{ $r->nazwy_uslug }}</h2>
                    <p class="text-sm font-mono text-zinc-300">
                        {{ $r->termin_rozpoczecia->format('H:i') }} – {{ $r->termin_zakonczenia->format('H:i') }} · {{ $r->laczny_czas }} min
                    </p>
                </div>

                <div class="px-5 py-4 space-y-3.5 border-b border-zinc-800 text-sm">
                    <div>
                        <p class="text-[11px] font-mono text-zinc-400 mb-0.5">KLIENT</p>
                        <p class="text-zinc-100">{{ $r->klient->imie }} {{ $r->klient->nazwisko }}</p>
                        @if ($r->klient->telefon)
                            <a href="tel:{{ str_replace(' ', '', $r->klient->telefon) }}" class="text-[13px] text-zinc-300 hover:text-orange-400">{{ $r->klient->telefon }}</a>
                        @endif
                    </div>
                    <div>
                        <p class="text-[11px] font-mono text-zinc-400 mb-0.5">POJAZD</p>
                        <p class="text-zinc-100">{{ $r->pojazd->marka }} {{ $r->pojazd->model }}</p>
                        <p class="text-xs font-mono text-zinc-300">{{ $r->pojazd->numer_rejestracyjny }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-mono text-zinc-400 mb-0.5">MECHANIK</p>
                        <p class="{{ $r->mechanik ? 'text-zinc-100' : 'text-red-400' }}">
                            {{ $r->mechanik ? $r->mechanik->imie . ' ' . $r->mechanik->nazwisko : 'nieprzydzielony' }}
                        </p>
                    </div>
                    @if ($r->uwagi)
                        <div>
                            <p class="text-[11px] font-mono text-zinc-400 mb-0.5">UWAGI KLIENTA</p>
                            <p class="text-[13px] text-zinc-200">„{{ $r->uwagi }}”</p>
                        </div>
                    @endif
                    <div class="flex justify-between items-baseline">
                        <span class="text-[13px] text-zinc-400">Kwota</span>
                        <span class="font-bold text-zinc-50">{{ number_format($r->laczna_cena, 2, ',', ' ') }} zł</span>
                    </div>
                </div>

                <div class="px-5 py-4 space-y-2">
                    @if ($r->status === 'oczekująca')
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('pracownik.reservations.approve', $r) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full min-h-[44px] bg-green-600 hover:bg-green-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">Zatwierdź</button>
                            </form>
                            <button type="button" onclick="document.getElementById('modal-odrzucenia').classList.remove('hidden')"
                                    class="flex-1 min-h-[44px] border border-red-800 text-red-400 hover:bg-red-900/30 text-sm font-semibold transition" style="border-radius: 2px;">Odrzuć</button>
                        </div>
                    @elseif ($r->status === 'zatwierdzona')
                        <form method="POST" action="{{ route('pracownik.reservations.complete', $r) }}">
                            @csrf
                            <button type="submit" class="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">Zakończ wizytę</button>
                        </form>
                    @endif

                    @if (in_array($r->status, ['oczekująca', 'zatwierdzona']))
                        <div class="flex gap-2">
                            @if ($przyszla && auth()->user()->rola === 'administrator')
                                <a href="{{ route('pracownik.reservations.przydzial', $r) }}"
                                   class="flex-1 min-h-[40px] inline-flex items-center justify-center border border-zinc-700 text-zinc-300 hover:text-white text-[13px]" style="border-radius: 2px;">Mechanik</a>
                            @endif
                            <a href="{{ route('pracownik.reservations.edit-termin', $r) }}"
                               class="flex-1 min-h-[40px] inline-flex items-center justify-center border border-zinc-700 text-zinc-300 hover:text-white text-[13px]" style="border-radius: 2px;">Zmień termin</a>
                        </div>
                    @elseif ($r->status === 'zakończona')
                        <p class="text-[13px] text-zinc-400">Wizyta zakończona – klient dostał mail z podsumowaniem.</p>
                    @endif
                </div>

                {{-- Okienko odrzucenia --}}
                @if ($r->status === 'oczekująca')
                    <div id="modal-odrzucenia" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 px-4">
                        <div class="bg-[#1F2128] border border-zinc-800 p-6 max-w-sm w-full" style="border-radius: 2px;">
                            <p class="text-zinc-200 font-semibold mb-3">Odrzuć rezerwację</p>
                            <form method="POST" action="{{ route('pracownik.reservations.reject', $r) }}">
                                @csrf
                                <label class="block text-xs font-mono text-zinc-400 mb-1.5">POWÓD (OPCJONALNIE – TRAFI DO MAILA DO KLIENTA)</label>
                                <textarea name="powod" rows="3" placeholder="np. brak części, zły termin dla warsztatu"
                                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 mb-4 focus:outline-none focus:border-orange-600" style="border-radius: 2px;"></textarea>
                                <div class="flex gap-3">
                                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 transition" style="border-radius: 2px;">Odrzuć</button>
                                    <button type="button" onclick="document.getElementById('modal-odrzucenia').classList.add('hidden')"
                                            class="flex-1 border border-zinc-600 text-zinc-300 font-semibold py-2.5 transition" style="border-radius: 2px;">Anuluj</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @else
                <div class="p-8 text-center">
                    <p class="text-sm text-zinc-300 mb-1">Brak wizyt w tym dniu</p>
                    <p class="text-xs text-zinc-400">Przejdź do innego dnia strzałkami u góry.</p>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection