@extends('layouts.admin')
@section('title', 'Stanowiska')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">STANOWISKA</p>
        <h1 class="text-2xl font-bold text-zinc-50">Stanowiska warsztatowe</h1>
    </div>
    <a href="{{ route('admin.workbays.create') }}"
       class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
        + Dodaj stanowisko
    </a>
</div>

@error('error')
    <p class="text-sm text-red-500 mb-4">{{ $message }}</p>
@enderror

<div class="bg-[#1F2128] border border-zinc-800 overflow-hidden" style="border-radius: 2px;">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800 text-left">
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">NAZWA</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">TYP</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">PRZYSZŁE REZERWACJE</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">STATUS</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stanowiska as $stanowisko)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-zinc-200">{{ $stanowisko->nazwa }}</td>
                    <td class="px-4 py-3 text-zinc-400">{{ $stanowisko->typ_etykieta }}</td>
                    <td class="px-4 py-3 text-zinc-400 font-mono">{{ $stanowisko->przyszle_rezerwacje }}</td>
                    <td class="px-4 py-3">
                        @if ($stanowisko->czy_aktywne)
                            <span class="text-xs font-mono px-2 py-1 bg-green-900/30 text-green-400" style="border-radius: 2px;">AKTYWNE</span>
                        @else
                            <span class="text-xs font-mono px-2 py-1 bg-zinc-800 text-zinc-500" style="border-radius: 2px;">NIEAKTYWNE</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.workbays.edit', $stanowisko) }}" class="text-orange-500 hover:text-orange-400">Edytuj</a>
                        <form method="POST" action="{{ route('admin.workbays.destroy', $stanowisko) }}" class="inline"
                              onsubmit="return confirm('Na pewno usunąć to stanowisko?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-400">Usuń</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-zinc-500">Brak dodanych stanowisk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection