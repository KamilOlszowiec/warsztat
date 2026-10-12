@extends('layouts.admin')
@section('title', 'Specjalizacje')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">SPECJALIZACJE</p>
        <h1 class="text-2xl font-bold text-zinc-50">Specjalizacje mechaników</h1>
    </div>
    <a href="{{ route('admin.specializations.create') }}"
       class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
        + Dodaj specjalizację
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
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">MECHANICY</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">USŁUGI</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($specjalizacje as $spec)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-zinc-200">{{ $spec->nazwa }}</td>
                    <td class="px-4 py-3">
                        @forelse ($spec->pracownicy as $p)
                            <p class="text-xs text-zinc-400">{{ $p->imie }} {{ $p->nazwisko }}</p>
                        @empty
                            <p class="text-xs text-red-400">Nikt – usługi z tą specjalizacją nie będą dostępne</p>
                        @endforelse
                    </td>
                    <td class="px-4 py-3 text-zinc-400 font-mono">{{ $spec->uslugi_count }}</td>
                    <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                        <a href="{{ route('admin.specializations.edit', $spec) }}" class="text-orange-500 hover:text-orange-400">Edytuj</a>
                        <form method="POST" action="{{ route('admin.specializations.destroy', $spec) }}" class="inline"
                              onsubmit="return confirm('Na pewno usunąć tę specjalizację?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-400">Usuń</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-zinc-500">Brak specjalizacji.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection