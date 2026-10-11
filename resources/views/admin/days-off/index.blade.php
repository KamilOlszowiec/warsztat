@extends('layouts.admin')
@section('title', 'Dni wolne')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">DNI WOLNE</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Dni wolne warsztatu</h1>

{{-- Dodawanie --}}
<form method="POST" action="{{ route('admin.days-off.store') }}"
      class="bg-[#1F2128] border border-zinc-800 p-6 mb-6 grid grid-cols-1 md:grid-cols-[180px_1fr_auto] gap-3 items-end" style="border-radius: 2px;">
    @csrf
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">DATA</label>
        <input type="date" name="data" min="{{ now()->format('Y-m-d') }}" value="{{ old('data') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    </div>
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">OPIS (OPCJONALNIE)</label>
        <input type="text" name="opis" value="{{ old('opis') }}" placeholder="np. Wigilia, inwentaryzacja"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    </div>
    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
        + Dodaj dzień wolny
    </button>
    @error('data') <p class="text-sm text-red-500 md:col-span-3">{{ $message }}</p> @enderror
    @error('opis') <p class="text-sm text-red-500 md:col-span-3">{{ $message }}</p> @enderror
</form>

{{-- Lista nadchodzących --}}
<div class="bg-[#1F2128] border border-zinc-800 overflow-hidden" style="border-radius: 2px;">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800 text-left">
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">DATA</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">OPIS</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">AKTYWNE REZERWACJE</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dniWolne as $dzien)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-zinc-200 font-mono">{{ $dzien->data->format('d.m.Y') }}</td>
                    <td class="px-4 py-3 text-zinc-400">{{ $dzien->opis ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono {{ $dzien->liczba_rezerwacji > 0 ? 'text-red-400' : 'text-zinc-400' }}">
                        {{ $dzien->liczba_rezerwacji }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.days-off.destroy', $dzien) }}" class="inline"
                              onsubmit="return confirm('Na pewno usunąć ten dzień wolny?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-400">Usuń</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-zinc-500">Brak zaplanowanych dni wolnych.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection