{{-- resources/views/klient/vehicles/index.blade.php --}}
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

<div class="bg-[#1F2128] border border-zinc-800 overflow-hidden" style="border-radius: 2px;">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800 text-left">
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">MARKA I MODEL</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">ROK</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">NR REJESTRACYJNY</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">VIN</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pojazdy as $pojazd)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3">
                        <a href="{{ route('klient.vehicles.show', $pojazd) }}" class="text-zinc-200 hover:text-orange-500 font-medium transition">
                            {{ $pojazd->marka }} {{ $pojazd->model }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-zinc-400">{{ $pojazd->rok_produkcji ?? '—' }}</td>
                    <td class="px-4 py-3 text-zinc-400 font-mono">{{ $pojazd->numer_rejestracyjny }}</td>
                    <td class="px-4 py-3 text-zinc-500 font-mono text-xs">{{ $pojazd->vin ?? '—' }}</td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('klient.vehicles.edit', $pojazd) }}" class="text-orange-500 hover:text-orange-400">Edytuj</a>
                        <form method="POST" action="{{ route('klient.vehicles.destroy', $pojazd) }}" class="inline"
                              onsubmit="return confirm('Na pewno usunąć ten pojazd?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-400">Usuń</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-zinc-500">Nie masz jeszcze żadnych pojazdów.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection