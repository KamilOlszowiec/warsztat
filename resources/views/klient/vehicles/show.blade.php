{{-- resources/views/klient/vehicles/show.blade.php --}}
@extends('layouts.klient')
@section('title', $pojazd->marka . ' ' . $pojazd->model)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">POJAZD</p>
        <h1 class="text-2xl font-bold text-zinc-50">{{ $pojazd->marka }} {{ $pojazd->model }}</h1>
    </div>
    <div class="space-x-3">
        <a href="{{ route('klient.vehicles.edit', $pojazd) }}"
           class="border border-zinc-600 hover:border-zinc-400 text-zinc-300 text-sm font-medium px-4 py-2.5 transition" style="border-radius: 2px;">
            Edytuj
        </a>
        <a href="{{ route('klient.reservations.create') }}"
           class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
            + Umów wizytę
        </a>
    </div>
</div>

<div class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg mb-6" style="border-radius: 2px;">
    <dl class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-xs font-mono text-zinc-500 mb-1">ROK PRODUKCJI</dt>
            <dd class="text-zinc-200">{{ $pojazd->rok_produkcji ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-mono text-zinc-500 mb-1">NR REJESTRACYJNY</dt>
            <dd class="text-zinc-200 font-mono">{{ $pojazd->numer_rejestracyjny }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-xs font-mono text-zinc-500 mb-1">VIN</dt>
            <dd class="text-zinc-200 font-mono text-xs">{{ $pojazd->vin ?? '—' }}</dd>
        </div>
    </dl>
</div>

<p class="text-sm font-mono text-orange-500 mb-3">HISTORIA REZERWACJI</p>

<div class="bg-[#1F2128] border border-zinc-800 overflow-hidden" style="border-radius: 2px;">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800 text-left">
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">USŁUGA</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">TERMIN</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rezerwacje as $r)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-zinc-200">{{ $r->nazwy_uslug }}</td>
                    <td class="px-4 py-3 text-zinc-400">{{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3">
                        @php
                            $kolory = [
                                'oczekująca' => 'bg-yellow-900/30 text-yellow-400',
                                'zatwierdzona' => 'bg-green-900/30 text-green-400',
                                'odrzucona' => 'bg-red-900/30 text-red-400',
                                'anulowana' => 'bg-zinc-800 text-zinc-500',
                                'zakończona' => 'bg-blue-900/30 text-blue-400',
                            ];
                        @endphp
                        <span class="text-xs font-mono px-2 py-1 {{ $kolory[$r->status] }}" style="border-radius: 2px;">
                            {{ strtoupper($r->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-zinc-500">Ten pojazd nie ma jeszcze żadnej historii rezerwacji.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection