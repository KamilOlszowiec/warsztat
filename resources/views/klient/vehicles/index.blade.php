@extends('layouts.klient')
@section('title', 'Moje pojazdy')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">POJAZDY</p>
        <h1 class="text-2xl font-bold text-zinc-50">Moje pojazdy</h1>
    </div>
    <a href="{{ route('klient.vehicles.create') }}"
       class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
        + Dodaj pojazd
    </a>
</div>

@error('error')
    <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
        {{ $message }}
    </div>
@enderror

@php
    $kolory = [
        'oczekująca'   => 'bg-yellow-900/30 text-yellow-400',
        'zatwierdzona' => 'bg-green-900/30 text-green-400',
        'odrzucona'    => 'bg-red-900/30 text-red-400',
        'anulowana'    => 'bg-zinc-800 text-zinc-500',
        'zakończona'   => 'bg-blue-900/30 text-blue-400',
    ];
    $kropki = [
        'oczekująca'   => 'bg-yellow-400',
        'zatwierdzona' => 'bg-green-400',
        'odrzucona'    => 'bg-red-400',
        'anulowana'    => 'bg-zinc-600',
        'zakończona'   => 'bg-blue-400',
    ];
@endphp

@if ($pojazdy->isEmpty())
    <div class="bg-[#1F2128] border border-zinc-800 p-8 text-center max-w-lg" style="border-radius: 2px;">
        <p class="text-zinc-300 font-medium mb-1">Nie masz jeszcze żadnego pojazdu</p>
        <p class="text-sm text-zinc-500 mb-4">Dodaj samochód, żeby umówić wizytę i śledzić historię napraw.</p>
        <a href="{{ route('klient.vehicles.create') }}" class="text-orange-500 hover:text-orange-400 font-medium">Dodaj pierwszy pojazd →</a>
    </div>
@else
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
    @foreach ($pojazdy as $pojazd)
        @php
            $historia = $pojazd->rezerwacje;
            $wykonane = $historia->where('status', 'zakończona');
            $ostatnia = $wykonane->first(); // posortowane od najnowszej
            $najblizsza = $historia
                ->filter(fn ($r) => in_array($r->status, ['oczekująca', 'zatwierdzona']) && $r->termin_rozpoczecia->isFuture())
                ->sortBy('termin_rozpoczecia')
                ->first();
        @endphp

        <div class="bg-[#1F2128] border border-zinc-800 hover:border-zinc-700 transition" style="border-radius: 2px;">
            <details class="group">
                {{-- Nagłówek kafelka (klikalny) --}}
                <summary class="list-none cursor-pointer p-5 [&::-webkit-details-marker]:hidden">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-zinc-50 truncate">{{ $pojazd->marka }} {{ $pojazd->model }}</h2>
                            <p class="text-xs font-mono text-zinc-500 mt-0.5 truncate">
                                {{ $pojazd->rok_produkcji ?? '—' }}
                                @if ($pojazd->vin) · VIN {{ $pojazd->vin }} @endif
                            </p>
                        </div>
                        {{-- Tablica rejestracyjna --}}
                        <div class="flex items-stretch shrink-0 border border-zinc-400 overflow-hidden" style="border-radius: 3px;">
                            <span class="bg-blue-700 w-2.5"></span>
                            <span class="bg-zinc-100 text-zinc-900 font-mono font-bold text-sm tracking-wider px-2 py-1">{{ $pojazd->numer_rejestracyjny }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-xs">
                        <div>
                            <p class="font-mono text-zinc-500 mb-0.5">WIZYTY</p>
                            <p class="text-zinc-100 text-base font-semibold">{{ $wykonane->count() }}</p>
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

                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-zinc-800 text-xs text-zinc-400 group-hover:text-zinc-200">
                        <span>Historia wizyt ({{ $historia->count() }})</span>
                        <span class="transition-transform group-open:rotate-180">▾</span>
                    </div>
                </summary>

                {{-- Historia (po rozwinięciu) --}}
                <div class="px-5 pb-5">
                    @if ($historia->isEmpty())
                        <p class="text-sm text-zinc-500">
                            Ten pojazd nie był jeszcze w warsztacie.
                            <a href="{{ route('klient.reservations.create', ['pojazd' => $pojazd->id_pojazdu]) }}" class="text-orange-500 hover:text-orange-400">Umów pierwszą wizytę →</a>
                        </p>
                    @else
                        <ol class="relative border-l border-zinc-800 ml-1.5">
                            @foreach ($historia as $r)
                                <li class="relative pl-5 {{ $loop->last ? '' : 'pb-4' }}">
                                    <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 {{ $kropki[$r->status] ?? 'bg-zinc-600' }}" style="border-radius: 50%;"></span>

                                    <div class="flex items-center justify-between gap-3 mb-1">
                                        <p class="text-sm font-mono text-zinc-300">{{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}</p>
                                        <span class="text-[10px] font-mono px-1.5 py-0.5 {{ $kolory[$r->status] ?? '' }}" style="border-radius: 2px;">
                                            {{ mb_strtoupper($r->status, 'UTF-8') }}
                                        </span>
                                    </div>

                                    <p class="text-sm text-zinc-100">{{ $r->nazwy_uslug }}</p>

                                    <p class="text-xs text-zinc-500 mt-0.5">
                                        {{ number_format($r->laczna_cena, 2) }} zł
                                        @if ($r->mechanik)
                                            · mechanik: {{ $r->mechanik->imie }} {{ $r->mechanik->nazwisko }}
                                        @endif
                                    </p>

                                    @if ($r->status === 'odrzucona' && $r->powod_odrzucenia)
                                        <p class="text-xs text-red-400/80 mt-1">Powód odrzucenia: {{ $r->powod_odrzucenia }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </details>

            {{-- Akcje (poza klikalnym nagłówkiem) --}}
            <div class="flex items-center gap-4 px-5 py-3 border-t border-zinc-800 text-sm">
                <a href="{{ route('klient.reservations.create', ['pojazd' => $pojazd->id_pojazdu]) }}" class="text-orange-500 hover:text-orange-400 font-medium">Umów wizytę</a>
                <a href="{{ route('klient.vehicles.edit', $pojazd) }}" class="text-zinc-400 hover:text-zinc-200">Edytuj</a>
                <form method="POST" action="{{ route('klient.vehicles.destroy', $pojazd) }}" class="ml-auto"
                      onsubmit="return confirm('Na pewno usunąć ten pojazd?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500/80 hover:text-red-400">Usuń</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endif
@endsection