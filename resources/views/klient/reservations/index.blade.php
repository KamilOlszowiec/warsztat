@extends('layouts.klient')
@section('title', 'Moje rezerwacje')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">REZERWACJE</p>
        <h1 class="text-2xl font-bold text-zinc-50">Moje rezerwacje</h1>
    </div>
    <a href="{{ route('klient.reservations.create') }}"
       class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
        + Nowa rezerwacja
    </a>
</div>

@error('error')
    <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
        {{ $message }}
    </div>
@enderror

@php
    $kolory = [
        'oczekująca' => 'bg-yellow-900/30 text-yellow-400',
        'zatwierdzona' => 'bg-green-900/30 text-green-400',
        'odrzucona' => 'bg-red-900/30 text-red-400',
        'anulowana' => 'bg-zinc-800 text-zinc-500',
        'zakończona' => 'bg-blue-900/30 text-blue-400',
    ];
@endphp

<div class="bg-[#1F2128] border border-zinc-800 overflow-x-auto" style="border-radius: 2px;">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800 text-left">
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">USŁUGA</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">POJAZD</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">TERMIN</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">STATUS</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rezerwacje as $r)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-zinc-200">
                        <a href="{{ route('klient.reservations.show', $r) }}" class="hover:text-orange-400">{{ $r->nazwy_uslug }}</a>
                    </td>
                    <td class="px-4 py-3 text-zinc-400">{{ $r->pojazd->marka }} {{ $r->pojazd->model }}</td>
                    <td class="px-4 py-3 text-zinc-400 whitespace-nowrap">
                        {{ $r->termin_rozpoczecia->format('d.m.Y') }}
                        <span class="text-zinc-300">{{ $r->termin_rozpoczecia->format('H:i') }}–{{ $r->termin_zakonczenia->format('H:i') }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-mono px-2 py-1 {{ $kolory[$r->status] }}" style="border-radius: 2px;">
                            {{ mb_strtoupper($r->status, 'UTF-8') }}
                        </span>
                        @if ($r->status === 'odrzucona' && $r->powod_odrzucenia)
                            <p class="text-xs text-zinc-500 mt-1">{{ $r->powod_odrzucenia }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex gap-3">
                            <a href="{{ route('klient.reservations.show', $r) }}" class="text-orange-500 hover:text-orange-400">Szczegóły</a>
                            @if (in_array($r->status, ['oczekująca', 'zatwierdzona']) && $r->termin_rozpoczecia->isFuture())
                                <form method="POST" action="{{ route('klient.reservations.cancel', $r) }}" class="inline"
                                      onsubmit="return confirm('Na pewno anulować tę rezerwację?');">
                                    @csrf
                                    <button type="submit" class="text-red-500 hover:text-red-400">Anuluj</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-zinc-500">Nie masz jeszcze żadnych rezerwacji.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection