@extends('layouts.klient')
@section('title', 'Moje rezerwacje')

@section('content')
@php
    $kolory = [
        'oczekująca'   => 'bg-yellow-900/40 text-yellow-400',
        'zatwierdzona' => 'bg-green-900/40 text-green-400',
        'odrzucona'    => 'bg-red-900/40 text-red-400',
        'anulowana'    => 'bg-zinc-800 text-zinc-400',
        'zakończona'   => 'bg-blue-900/40 text-blue-400',
    ];

    // "dziś", "jutro", "za X dni"
    $kiedy = function ($termin) {
        $dni = (int) today()->diffInDays($termin->copy()->startOfDay());
        return match (true) {
            $dni <= 0 => 'dziś',
            $dni === 1 => 'jutro',
            default => "za {$dni} dni",
        };
    };

    $nadchodzaceWgMiesiecy = $nadchodzace->groupBy(fn ($r) => $r->termin_rozpoczecia->format('Y-m'));

    $zakladki = [
        'nadchodzace' => ['Nadchodzące', $nadchodzace->count()],
        'historia'    => ['Historia', $historia->count()],
    ];
@endphp

<div class="max-w-4xl">

    {{-- Nagłówek --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">REZERWACJE</p>
            <h1 class="text-2xl font-bold text-zinc-50">Moje rezerwacje</h1>
        </div>
        <a href="{{ route('klient.reservations.create') }}"
           class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            + Nowa rezerwacja
        </a>
    </div>

    @error('error')
        <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
            {{ $message }}
        </div>
    @enderror

    {{-- Zakładki --}}
    <nav class="flex gap-1 border-b border-zinc-800 mb-5" aria-label="Rodzaj rezerwacji">
        @foreach ($zakladki as $klucz => [$nazwa, $liczba])
            <a href="{{ route('klient.reservations.index', ['zakladka' => $klucz]) }}"
               @if ($zakladka === $klucz) aria-current="page" @endif
               class="inline-flex items-center gap-2 min-h-[44px] px-4 -mb-px border-b-2 text-sm transition
                      {{ $zakladka === $klucz ? 'border-orange-600 text-zinc-50 font-semibold' : 'border-transparent text-zinc-400 hover:text-zinc-200 font-medium' }}">
                {{ $nazwa }}
                <span class="px-1.5 min-w-[20px] text-center text-[11px] {{ $zakladka === $klucz ? 'bg-zinc-700 text-zinc-50' : 'bg-zinc-800 text-zinc-300' }}" style="border-radius: 10px;">
                    {{ $liczba }}
                </span>
            </a>
        @endforeach
    </nav>

    {{-- NADCHODZĄCE --}}
    @if ($zakladka === 'nadchodzace')
        @forelse ($nadchodzaceWgMiesiecy as $miesiac => $rezerwacjeMiesiaca)
            <p class="text-[11px] font-mono tracking-widest text-zinc-400 mb-3 {{ $loop->first ? '' : 'mt-8' }}">
                {{ mb_strtoupper($rezerwacjeMiesiaca->first()->termin_rozpoczecia->translatedFormat('F Y'), 'UTF-8') }}
            </p>

            <div class="space-y-3">
                @foreach ($rezerwacjeMiesiaca as $r)
                    <article class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
                        <div class="flex flex-wrap items-center gap-5 p-5">

                            {{-- Kartka z kalendarza --}}
                            <div class="w-16 shrink-0 text-center py-2.5 bg-[#18191D] border border-zinc-800" style="border-radius: 2px;">
                                <p class="text-[10px] font-mono text-zinc-400">{{ mb_strtoupper($r->termin_rozpoczecia->translatedFormat('D'), 'UTF-8') }}</p>
                                <p class="text-2xl font-bold text-zinc-50 leading-tight">{{ $r->termin_rozpoczecia->format('d') }}</p>
                                <p class="text-[10px] font-mono text-zinc-400">{{ mb_strtoupper($r->termin_rozpoczecia->translatedFormat('M'), 'UTF-8') }}</p>
                            </div>

                            <div class="flex-1 min-w-0 basis-[300px]">
                                <div class="flex flex-wrap items-center gap-2.5 mb-1.5">
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 {{ $kolory[$r->status] ?? '' }}" style="border-radius: 2px;">
                                        {{ mb_strtoupper($r->status, 'UTF-8') }}
                                    </span>
                                    <span class="text-sm text-zinc-400">{{ $kiedy($r->termin_rozpoczecia) }}</span>
                                </div>
                                <h2 class="text-[17px] font-semibold text-zinc-50">{{ $r->nazwy_uslug }}</h2>
                                <p class="text-sm text-zinc-300 mt-1.5">
                                    {{ $r->termin_rozpoczecia->format('H:i') }} – {{ $r->termin_zakonczenia->format('H:i') }}
                                    · {{ $r->pojazd->marka }} {{ $r->pojazd->model }} ({{ $r->pojazd->numer_rejestracyjny }})
                                    @if ($r->mechanik)
                                        · mechanik: {{ $r->mechanik->imie }} {{ $r->mechanik->nazwisko }}
                                    @endif
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-lg font-bold text-zinc-50">{{ number_format($r->laczna_cena, 2, ',', ' ') }} zł</p>
                                <p class="text-xs text-zinc-400 mt-0.5">szacowany koszt</p>
                            </div>
                        </div>

                        {{-- Pasek akcji --}}
                        <div class="flex flex-wrap items-center gap-5 px-5 py-3 border-t border-zinc-800 bg-[#1C1D22]">
                            @if ($r->status === 'oczekująca')
                                <span class="text-sm text-yellow-400">Czeka na potwierdzenie warsztatu – dostaniesz e-mail.</span>
                            @endif
                            <div class="ml-auto flex items-center gap-5">
                                <a href="{{ route('klient.reservations.edit-termin', $r) }}" class="min-h-[36px] inline-flex items-center text-sm text-zinc-300 hover:text-zinc-100">Zmień termin</a>
                                <form method="POST" action="{{ route('klient.reservations.cancel', $r) }}"
                                      onsubmit="return confirm('Na pewno anulować tę rezerwację?');">
                                    @csrf
                                    <button type="submit" class="min-h-[36px] text-sm text-red-400 hover:text-red-300">Anuluj</button>
                                </form>
                                <a href="{{ route('klient.reservations.show', $r) }}" class="text-sm font-semibold text-orange-500 hover:text-orange-400">Szczegóły →</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @empty
            <div class="bg-[#1F2128] border border-dashed border-zinc-600 p-10 text-center" style="border-radius: 2px;">
                <p class="text-zinc-200 font-medium mb-1">Nie masz zaplanowanych wizyt</p>
                <p class="text-sm text-zinc-400 mb-4">Wybierz usługi i wolny termin – zajmie to około minuty.</p>
                <a href="{{ route('klient.reservations.create') }}" class="text-sm font-semibold text-orange-500 hover:text-orange-400">Umów wizytę →</a>
            </div>
        @endforelse
    @endif

    {{-- HISTORIA --}}
    @if ($zakladka === 'historia')
        <div class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
            @forelse ($historia as $r)
                <a href="{{ route('klient.reservations.show', $r) }}"
                   class="flex items-center gap-4 px-5 py-4 border-b border-zinc-800 hover:bg-white/[0.02] transition">
                    <div class="w-12 shrink-0 text-center">
                        <p class="text-2xl font-bold leading-none text-zinc-400">{{ $r->termin_rozpoczecia->format('d') }}</p>
                        <p class="text-[11px] font-mono text-zinc-400 mt-1">{{ mb_strtoupper($r->termin_rozpoczecia->translatedFormat('M'), 'UTF-8') }}</p>
                        <p class="text-[10px] font-mono text-zinc-500">{{ $r->termin_rozpoczecia->format('Y') }}</p>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-300 truncate">{{ $r->nazwy_uslug }}</p>
                        <p class="text-xs text-zinc-400 mt-0.5 truncate">
                            {{ $r->termin_rozpoczecia->translatedFormat('D') }},
                            {{ $r->termin_rozpoczecia->format('H:i') }} – {{ $r->termin_zakonczenia->format('H:i') }}
                            · {{ $r->pojazd->marka }} {{ $r->pojazd->model }}
                            @if ($r->mechanik) · {{ $r->mechanik->imie }} {{ $r->mechanik->nazwisko }} @endif
                        </p>
                        @if ($r->status === 'odrzucona' && $r->powod_odrzucenia)
                            <p class="text-xs text-red-400/80 mt-1 truncate">Powód: {{ $r->powod_odrzucenia }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 flex flex-col items-end gap-1.5">
                        <span class="text-sm font-semibold text-zinc-300">{{ number_format($r->laczna_cena, 2, ',', ' ') }} zł</span>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 {{ $kolory[$r->status] ?? '' }}" style="border-radius: 2px;">
                            {{ mb_strtoupper($r->status, 'UTF-8') }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="p-10 text-center">
                    <p class="text-zinc-200 font-medium mb-1">Historia jest pusta</p>
                    <p class="text-sm text-zinc-400">Tu trafią wizyty zakończone, odrzucone i anulowane.</p>
                </div>
            @endforelse

            @if ($historia->isNotEmpty())
                <p class="px-5 py-3.5 text-xs text-zinc-400">Wizyty zakończone, odrzucone i anulowane – od najnowszej.</p>
            @endif
        </div>
    @endif
</div>
@endsection