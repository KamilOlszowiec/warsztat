@extends('layouts.klient')
@section('title', 'Panel klienta')

@section('content')

<div class="max-w-5xl space-y-6">

    {{-- Nagłówek --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">PANEL GŁÓWNY</p>
            <h1 class="text-3xl font-bold text-zinc-50">Witaj, {{ auth()->user()->imie }} 👋</h1>
            <p class="text-sm text-zinc-400 mt-1.5">{{ today()->translatedFormat('l, j F Y') }}</p>
        </div>
        <a href="{{ route('klient.reservations.create') }}"
           class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            + Umów nową wizytę
        </a>
    </div>

    {{-- Najbliższa wizyta / pierwszy krok --}}
    @if ($najblizsza)
        <section class="border border-orange-600 bg-orange-600/10 p-6 md:px-7 flex flex-wrap items-end justify-between gap-6" style="border-radius: 2px;">
            <div class="flex-1 min-w-0 basis-[520px] space-y-3.5">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="text-xs font-mono text-orange-400">NAJBLIŻSZA WIZYTA</span>
                    <x-status :status="$najblizsza->status" rozmiar="sredni" />
                    <span class="text-sm text-zinc-400">{{ $kiedy }}</span>
                </div>

                <h2 class="text-2xl font-bold text-zinc-50 leading-tight">{{ $najblizsza->nazwy_uslug }}</h2>

                <div class="grid grid-cols-[repeat(auto-fit,minmax(170px,1fr))] gap-x-6 gap-y-4">
                    <div>
                        <p class="text-[11px] font-mono text-zinc-400 mb-1">TERMIN</p>
                        <p class="text-zinc-100">{{ $najblizsza->termin_rozpoczecia->translatedFormat('D, j F') }}</p>
                        <p class="text-zinc-50 font-semibold mt-0.5">
                            {{ $najblizsza->termin_rozpoczecia->format('H:i') }} – {{ $najblizsza->termin_zakonczenia->format('H:i') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] font-mono text-zinc-400 mb-1">POJAZD</p>
                        <p class="text-zinc-100">{{ $najblizsza->pojazd->marka }} {{ $najblizsza->pojazd->model }}</p>
                        <p class="text-sm font-mono text-zinc-300 mt-0.5">{{ $najblizsza->pojazd->numer_rejestracyjny }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-mono text-zinc-400 mb-1">MECHANIK</p>
                        <p class="text-zinc-100">{{ $najblizsza->mechanik ? $najblizsza->mechanik->imie . ' ' . $najblizsza->mechanik->nazwisko : 'zostanie przydzielony' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-mono text-zinc-400 mb-1">SZACOWANY KOSZT</p>
                        <p class="text-xl font-bold text-zinc-50">{{ number_format($najblizsza->laczna_cena, 2, ',', ' ') }} zł</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('klient.reservations.show', $najblizsza) }}"
               class="inline-flex items-center min-h-[44px] px-5 border border-orange-600 text-orange-400 hover:bg-orange-600 hover:text-white text-sm font-semibold transition" style="border-radius: 2px;">
                Zobacz szczegóły →
            </a>
        </section>
    @elseif ($pojazdy->isEmpty())
        <section class="bg-[#1F2128] border border-dashed border-zinc-600 p-8 flex flex-wrap items-center gap-6" style="border-radius: 2px;">
            <div class="w-14 h-14 shrink-0 flex items-center justify-center bg-orange-600/10" style="border-radius: 50%;">
                <svg aria-hidden="true" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-400">
                    <path d="M5 17h14v-5l-2-5H7l-2 5v5z"></path><path d="M5 12h14"></path><circle cx="8" cy="17" r="2"></circle><circle cx="16" cy="17" r="2"></circle>
                </svg>
            </div>
            <div class="flex-1 min-w-0 basis-[360px]">
                <h2 class="text-xl font-bold text-zinc-50 mb-1.5">Zacznij od dodania pojazdu</h2>
                <p class="text-sm text-zinc-400">Po dodaniu auta umówisz wizytę w kilka kliknięć, a tutaj zobaczysz najbliższy termin i historię napraw.</p>
            </div>
            <a href="{{ route('klient.vehicles.create') }}"
               class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
                Dodaj pojazd
            </a>
        </section>
    @else
        <section class="bg-[#1F2128] border border-dashed border-zinc-600 p-8 flex flex-wrap items-center gap-6" style="border-radius: 2px;">
            <div class="w-14 h-14 shrink-0 flex items-center justify-center bg-orange-600/10" style="border-radius: 50%;">
                <svg aria-hidden="true" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-400">
                    <rect x="3" y="5" width="18" height="16" rx="1"></rect><path d="M3 10h18M8 3v4M16 3v4"></path>
                </svg>
            </div>
            <div class="flex-1 min-w-0 basis-[360px]">
                <h2 class="text-xl font-bold text-zinc-50 mb-1.5">Nie masz zaplanowanych wizyt</h2>
                <p class="text-sm text-zinc-400">Wybierz usługi i wolny termin – zajmie to około minuty.</p>
            </div>
            <a href="{{ route('klient.reservations.create') }}"
               class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
                Umów wizytę
            </a>
        </section>
    @endif

    {{-- Liczniki --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-[#1F2128] border border-zinc-800 p-5 flex flex-col gap-1.5" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">TWOJE POJAZDY</p>
            <p class="text-3xl font-bold {{ $pojazdy->count() ? 'text-zinc-50' : 'text-zinc-500' }}">{{ $pojazdy->count() }}</p>
            <a href="{{ route('klient.vehicles.index') }}" class="text-sm font-medium text-orange-500 hover:text-orange-400">
                {{ $pojazdy->count() ? 'Moje pojazdy →' : 'Dodaj pierwszy →' }}
            </a>
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 p-5 flex flex-col gap-1.5" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">AKTYWNE REZERWACJE</p>
            <p class="text-3xl font-bold {{ $liczbaAktywnych ? 'text-zinc-50' : 'text-zinc-500' }}">{{ $liczbaAktywnych }}</p>
            @if ($liczbaOczekujacych)
                <p class="text-sm text-yellow-400">{{ $liczbaOczekujacych }} czeka na potwierdzenie</p>
            @else
                <p class="text-sm text-zinc-400">{{ $liczbaAktywnych ? 'wszystkie potwierdzone' : 'brak zaplanowanych wizyt' }}</p>
            @endif
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 p-5 flex flex-col gap-1.5" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">HISTORIA WIZYT</p>
            <p class="text-3xl font-bold {{ $liczbaZakonczonych ? 'text-zinc-50' : 'text-zinc-500' }}">{{ $liczbaZakonczonych }}</p>
            <p class="text-sm text-zinc-400">
                {{ $ostatniaWizyta ? 'ostatnia ' . $ostatniaWizyta->termin_rozpoczecia->format('d.m.Y') : 'jeszcze nic tu nie ma' }}
            </p>
        </div>
    </section>

    {{-- Dolna sekcja --}}
    <div class="flex flex-wrap items-start gap-6">

        {{-- Rezerwacje --}}
        <section class="flex-[2_1_480px] min-w-0 bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
            <div class="flex items-baseline justify-between gap-3 px-5 py-4 border-b border-zinc-800">
                <h2 class="font-semibold text-zinc-50">Rezerwacje</h2>
                <span class="text-[11px] font-mono text-zinc-400">NADCHODZĄCE I OSTATNIE</span>
            </div>

            @forelse ($lista as $r)
                @php
                    $nazwy = $r->uslugi->pluck('nazwa');
                    $opisUslug = $nazwy->count() > 2
                        ? $nazwy->take(2)->implode(', ') . ' +' . ($nazwy->count() - 2) . ' inne'
                        : $nazwy->implode(', ');
                    $przeszla = $r->termin_rozpoczecia->isPast() || !in_array($r->status, ['oczekująca', 'zatwierdzona']);
                @endphp
                <a href="{{ route('klient.reservations.show', $r) }}"
                   class="flex items-center gap-4 px-5 py-4 border-b border-zinc-800 hover:bg-white/[0.02] transition">
                    <div class="w-12 shrink-0 text-center">
                        <p class="text-2xl font-bold leading-none {{ $przeszla ? 'text-zinc-400' : 'text-zinc-50' }}">{{ $r->termin_rozpoczecia->format('d') }}</p>
                        <p class="text-[11px] font-mono text-zinc-400 mt-1">{{ mb_strtoupper($r->termin_rozpoczecia->translatedFormat('M'), 'UTF-8') }}</p>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate {{ $przeszla ? 'text-zinc-300' : 'text-zinc-100' }}">{{ $opisUslug }}</p>
                        <p class="text-xs text-zinc-400 mt-0.5 truncate">
                            {{ $r->termin_rozpoczecia->translatedFormat('D') }},
                            {{ $r->termin_rozpoczecia->format('H:i') }} – {{ $r->termin_zakonczenia->format('H:i') }}
                            · {{ $r->pojazd->marka }} {{ $r->pojazd->model }} ({{ $r->pojazd->numer_rejestracyjny }})
                        </p>
                    </div>
                    <div class="shrink-0 flex flex-col items-end gap-1.5">
                        <span class="text-sm font-semibold {{ $przeszla ? 'text-zinc-300' : 'text-zinc-50' }}">{{ number_format($r->laczna_cena, 2, ',', ' ') }} zł</span>
                        <x-status :status="$r->status" rozmiar="maly" />
                    </div>
                </a>
            @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-zinc-300 mb-1">Tu pojawią się Twoje wizyty</p>
                    <p class="text-sm text-zinc-400">Nadchodzące terminy i ostatnio zakończone naprawy.</p>
                </div>
            @endforelse

            @if ($lista->isNotEmpty() || $najblizsza)
                <div class="px-5 py-3.5">
                    <a href="{{ route('klient.reservations.index') }}" class="text-sm font-medium text-orange-500 hover:text-orange-400">Zobacz wszystkie rezerwacje →</a>
                </div>
            @endif
        </section>

        {{-- Garaż --}}
        <section class="flex-[1_1_280px] min-w-0 bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
            <div class="flex items-baseline justify-between px-5 py-4 border-b border-zinc-800">
                <h2 class="font-semibold text-zinc-50">Twój garaż</h2>
                <span class="text-[11px] font-mono text-zinc-400">
                    @if ($pojazdy->isEmpty())
                        PUSTO
                    @else
                        {{ $pojazdy->count() }} {{ $pojazdy->count() === 1 ? 'AUTO' : ($pojazdy->count() < 5 ? 'AUTA' : 'AUT') }}
                    @endif
                </span>
            </div>
            <div class="px-5 py-4 space-y-3">
                @foreach ($pojazdy->take(3) as $p)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-100 truncate">{{ $p->marka }} {{ $p->model }}</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="inline-flex items-stretch border border-zinc-400 overflow-hidden" style="border-radius: 3px;">
                                    <span class="w-2 bg-blue-700"></span>
                                    <span class="bg-zinc-100 text-zinc-900 font-mono font-bold text-xs tracking-wider px-1.5 py-0.5">{{ $p->numer_rejestracyjny }}</span>
                                </span>
                                <span class="text-xs text-zinc-400">
                                    {{ $p->rok_produkcji ? $p->rok_produkcji . ' · ' : '' }}{{ $p->liczba_wizyt }} {{ $p->liczba_wizyt === 1 ? 'wizyta' : ($p->liczba_wizyt >= 2 && $p->liczba_wizyt <= 4 ? 'wizyty' : 'wizyt') }}
                                </span>
                            </div>
                        </div>
                        <a href="{{ route('klient.reservations.create', ['pojazd' => $p->id_pojazdu]) }}"
                           class="inline-flex items-center min-h-[44px] text-sm font-semibold text-orange-500 hover:text-orange-400 whitespace-nowrap">
                            Umów to auto
                        </a>
                    </div>
                @endforeach

                @if ($pojazdy->count() > 3)
                    <a href="{{ route('klient.vehicles.index') }}" class="block text-sm text-zinc-400 hover:text-zinc-200">
                        i {{ $pojazdy->count() - 3 }} więcej – wszystkie pojazdy →
                    </a>
                @endif

                <a href="{{ route('klient.vehicles.create') }}"
                   class="flex items-center justify-center {{ $pojazdy->isEmpty() ? 'min-h-[64px]' : 'min-h-[44px]' }} border border-dashed border-zinc-600 text-zinc-300 hover:border-orange-600 hover:text-orange-400 text-sm font-medium transition" style="border-radius: 2px;">
                    {{ $pojazdy->isEmpty() ? '+ Dodaj pierwsze auto' : '+ Dodaj auto' }}
                </a>
            </div>
        </section>
    </div>
</div>
@endsection