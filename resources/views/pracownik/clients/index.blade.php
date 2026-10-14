@extends('layouts.pracownik')
@section('title', 'Klienci i pojazdy')

@section('content')
@php
    $odmiana = fn ($n, $jeden, $kilka, $wiele) => $n === 1 ? $jeden
        : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14) ? $kilka : $wiele);

    $kolory = [
        'oczekująca'   => 'bg-yellow-900/40 text-yellow-400',
        'zatwierdzona' => 'bg-green-900/40 text-green-400',
        'odrzucona'    => 'bg-red-900/40 text-red-400',
        'anulowana'    => 'bg-zinc-800 text-zinc-400',
        'zakończona'   => 'bg-blue-900/40 text-blue-400',
    ];
    $kropki = [
        'oczekująca'   => 'bg-yellow-400',
        'zatwierdzona' => 'bg-green-400',
        'odrzucona'    => 'bg-red-400',
        'anulowana'    => 'bg-zinc-500',
        'zakończona'   => 'bg-blue-400',
    ];

    // Fraza bez spacji i wielkich liter – do sprawdzania, czy szukano po tablicy
    $fraza = mb_strtolower(str_replace(' ', '', $q), 'UTF-8');
@endphp

<div class="max-w-6xl">
    <p class="text-sm font-mono text-orange-500 mb-1">KLIENCI</p>
    <h1 class="text-2xl font-bold text-zinc-50 mb-5">Klienci i pojazdy</h1>

    {{-- Wyszukiwarka --}}
    <form method="GET" action="{{ route('pracownik.clients.index') }}"
          class="flex items-center gap-3 px-4 mb-5 bg-[#1F2128] border border-zinc-700 focus-within:border-orange-600 transition" style="border-radius: 2px;">
        <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-zinc-400 shrink-0">
            <circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path>
        </svg>
        <label for="szukaj" class="sr-only">Szukaj klienta lub pojazdu</label>
        <input id="szukaj" type="search" name="q" value="{{ $q }}" autofocus
               placeholder="Nazwisko, telefon albo numer rejestracyjny, np. LU12…"
               class="flex-1 min-w-0 h-[52px] bg-transparent text-zinc-50 placeholder-zinc-500 focus:outline-none">
        <span class="text-xs text-zinc-400 whitespace-nowrap hidden sm:inline">
            {{ $klienci->count() }}{{ $obcieto ? '+' : '' }} {{ $odmiana($klienci->count(), 'klient', 'klientów', 'klientów') }}
        </span>
        @if ($q !== '')
            <a href="{{ route('pracownik.clients.index') }}" class="text-sm text-zinc-400 hover:text-zinc-200 whitespace-nowrap">Wyczyść</a>
        @endif
        <button type="submit" class="h-9 px-4 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            Szukaj
        </button>
    </form>

    <div class="flex flex-wrap gap-5 items-start">

        {{-- Lista wyników --}}
        <section aria-label="Wyniki" class="flex-[1_1_300px] min-w-0 max-w-[380px] bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
            @forelse ($klienci as $k)
                @php
                    $zaznaczony = $wybrany && $wybrany->id_uzytkownika === $k->id_uzytkownika;
                    $wizyty = (int) ($liczbyWizyt[$k->id_uzytkownika] ?? 0);
                @endphp
                <a href="{{ route('pracownik.clients.index', array_filter(['q' => $q, 'klient' => $k->id_uzytkownika])) }}"
                   @if ($zaznaczony) aria-current="true" @endif
                   class="block px-4 py-3.5 border-b border-zinc-800 transition {{ $zaznaczony ? 'bg-orange-600/10 shadow-[inset_3px_0_0_#EA580C]' : 'hover:bg-white/[0.02]' }}">
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-sm font-semibold text-zinc-50">{{ $k->imie }} {{ $k->nazwisko }}</span>
                        <span class="text-xs text-zinc-400 whitespace-nowrap">{{ $wizyty }} {{ $odmiana($wizyty, 'wizyta', 'wizyty', 'wizyt') }}</span>
                    </div>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ $k->telefon ?: $k->email }}</p>
                    @if ($k->pojazdy->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach ($k->pojazdy as $p)
                                <span class="inline-flex items-stretch border border-zinc-400 overflow-hidden" style="border-radius: 3px;">
                                    <span class="w-1.5 bg-blue-700"></span>
                                    <span class="bg-zinc-100 text-zinc-900 font-mono font-bold text-[11px] tracking-wider px-1">{{ $p->numer_rejestracyjny }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </a>
            @empty
                <div class="px-5 py-8 text-center">
                    @if ($q !== '')
                        <p class="text-sm text-zinc-300 mb-1">Brak wyników dla „{{ $q }}”</p>
                        <p class="text-xs text-zinc-400">Sprawdź pisownię albo szukaj po samym numerze rejestracyjnym.</p>
                    @else
                        <p class="text-sm text-zinc-400">Nie ma jeszcze żadnych klientów.</p>
                    @endif
                </div>
            @endforelse

            @if ($obcieto)
                <p class="px-4 py-3 text-xs text-zinc-400">Pokazano pierwsze 50 wyników – doprecyzuj wyszukiwanie.</p>
            @endif
        </section>

        {{-- Karta klienta --}}
        @if ($wybrany)
            @php
                $wszystkieWizyty = $wybrany->pojazdy->sum(fn ($p) => $p->rezerwacje->count());
                $inicjaly = mb_strtoupper(mb_substr($wybrany->imie, 0, 1) . mb_substr($wybrany->nazwisko, 0, 1), 'UTF-8');
                $jedenPojazd = $wybrany->pojazdy->count() === 1;
            @endphp
            <section aria-label="Karta klienta" class="flex-[2_1_420px] min-w-0 space-y-3">

                <div class="bg-[#1F2128] border border-zinc-800 p-5 flex flex-wrap items-center gap-4" style="border-radius: 2px;">
                    <div class="w-[52px] h-[52px] shrink-0 flex items-center justify-center bg-orange-600 text-white text-lg font-bold" style="border-radius: 50%;">
                        {{ $inicjaly }}
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <h2 class="text-xl font-bold text-zinc-50">{{ $wybrany->imie }} {{ $wybrany->nazwisko }}</h2>
                        <p class="text-sm text-zinc-400 mt-1">
                            Klient od {{ $wybrany->created_at ? $wybrany->created_at->format('m.Y') : '—' }}
                            · {{ $wybrany->pojazdy->count() }} {{ $odmiana($wybrany->pojazdy->count(), 'pojazd', 'pojazdy', 'pojazdów') }},
                            {{ $wszystkieWizyty }} {{ $odmiana($wszystkieWizyty, 'wizyta', 'wizyty', 'wizyt') }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-1 text-sm">
                        @if ($wybrany->telefon)
                            <a href="tel:{{ str_replace(' ', '', $wybrany->telefon) }}" class="text-zinc-100 hover:text-orange-400">{{ $wybrany->telefon }}</a>
                        @endif
                        <a href="mailto:{{ $wybrany->email }}" class="text-zinc-300 hover:text-orange-400 text-[13px]">{{ $wybrany->email }}</a>
                    </div>
                </div>

                @forelse ($wybrany->pojazdy as $p)
                    @php
                        $historia = $p->rezerwacje; // posortowane od najnowszej
                        $ostatnia = $historia->firstWhere('status', 'zakończona');
                        $najblizsza = $historia
                            ->filter(fn ($r) => in_array($r->status, ['oczekująca', 'zatwierdzona']) && $r->termin_rozpoczecia->isFuture())
                            ->sortBy('termin_rozpoczecia')
                            ->first();

                        // Rozwinięte od razu: jedyne auto klienta albo auto pasujące do szukanej tablicy
                        $tablica = mb_strtolower(str_replace(' ', '', $p->numer_rejestracyjny), 'UTF-8');
                        $otwarte = $jedenPojazd || ($fraza !== '' && str_contains($tablica, $fraza));
                    @endphp

                    <details class="group bg-[#1F2128] border border-zinc-800 hover:border-zinc-700 open:border-zinc-700 transition" style="border-radius: 2px;" @if ($otwarte) open @endif>
                        <summary class="list-none cursor-pointer px-5 py-4 [&::-webkit-details-marker]:hidden">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-zinc-50">{{ $p->marka }} {{ $p->model }}</p>
                                    <p class="text-[11px] font-mono text-zinc-400 mt-0.5">
                                        {{ $p->rok_produkcji ?? '—' }}@if ($p->vin) · VIN {{ $p->vin }}@endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-stretch border border-zinc-400 overflow-hidden" style="border-radius: 3px;">
                                        <span class="w-2 bg-blue-700"></span>
                                        <span class="bg-zinc-100 text-zinc-900 font-mono font-bold text-sm tracking-wider px-2 py-0.5">{{ $p->numer_rejestracyjny }}</span>
                                    </span>
                                    <span class="text-zinc-400 transition-transform group-open:rotate-180" aria-hidden="true">▾</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mt-3 text-xs">
                                <div>
                                    <p class="font-mono text-zinc-500 mb-0.5">WIZYTY</p>
                                    <p class="text-zinc-100 font-semibold">{{ $historia->count() }}</p>
                                </div>
                                <div>
                                    <p class="font-mono text-zinc-500 mb-0.5">OSTATNIA</p>
                                    <p class="text-zinc-200">{{ $ostatnia ? $ostatnia->termin_rozpoczecia->format('d.m.Y') : '—' }}</p>
                                </div>
                                <div>
                                    <p class="font-mono text-zinc-500 mb-0.5">NAJBLIŻSZA</p>
                                    @if ($najblizsza)
                                        <p class="text-orange-400 font-medium">{{ $najblizsza->termin_rozpoczecia->format('d.m.Y H:i') }}</p>
                                    @else
                                        <p class="text-zinc-200">—</p>
                                    @endif
                                </div>
                            </div>
                        </summary>

                        <div class="px-5 pb-5 pt-1 border-t border-zinc-800">
                            <p class="text-[11px] font-mono text-zinc-400 mt-4 mb-3">HISTORIA WIZYT</p>
                            @if ($historia->isEmpty())
                                <p class="text-sm text-zinc-500">Ten pojazd nie był jeszcze w warsztacie.</p>
                            @else
                                <ol class="relative border-l border-zinc-700 ml-1.5">
                                    @foreach ($historia as $r)
                                        <li class="relative pl-5 {{ $loop->last ? '' : 'pb-4' }}">
                                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 {{ $kropki[$r->status] ?? 'bg-zinc-500' }}" style="border-radius: 50%;"></span>
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="text-xs font-mono text-zinc-300">{{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}</span>
                                                <span class="text-[10px] font-mono px-1.5 py-0.5 {{ $kolory[$r->status] ?? '' }}" style="border-radius: 2px;">
                                                    {{ mb_strtoupper($r->status, 'UTF-8') }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-zinc-100 mt-1">{{ $r->nazwy_uslug }}</p>
                                            <p class="text-xs text-zinc-400 mt-0.5">
                                                @if ($r->status === 'odrzucona' && $r->powod_odrzucenia)
                                                    Powód: {{ $r->powod_odrzucenia }}
                                                @else
                                                    {{ number_format($r->laczna_cena, 2, ',', ' ') }} zł
                                                    @if ($r->mechanik) · {{ $r->mechanik->imie }} {{ $r->mechanik->nazwisko }} @endif
                                                @endif
                                            </p>
                                            @if ($r->uwagi)
                                                <p class="text-xs text-zinc-500 mt-0.5">„{{ $r->uwagi }}”</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="bg-[#1F2128] border border-dashed border-zinc-700 p-8 text-center text-sm text-zinc-400" style="border-radius: 2px;">
                        Klient nie dodał jeszcze żadnego pojazdu.
                    </div>
                @endforelse
            </section>
        @endif
    </div>
</div>
@endsection