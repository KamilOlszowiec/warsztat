{{-- resources/views/admin/reservations/index.blade.php --}}
@extends('layouts.admin')
@section('title', 'Rezerwacje')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">REZERWACJE</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Wszystkie rezerwacje</h1>

<div class="flex gap-2 mb-4 flex-wrap">
    @foreach (['wszystkie' => 'Wszystkie', 'oczekująca' => 'Oczekujące', 'zatwierdzona' => 'Zatwierdzone', 'odrzucona' => 'Odrzucone', 'anulowana' => 'Anulowane', 'zakończona' => 'Zakończone'] as $key => $label)
        <a href="{{ route('admin.reservations.index', ['status' => $key]) }}"
           class="text-sm font-medium px-3 py-1.5 transition {{ $status === $key ? 'bg-orange-600 text-white' : 'bg-[#1F2128] text-zinc-400 hover:text-zinc-200' }}"
           style="border-radius: 2px;">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="bg-[#1F2128] border border-zinc-800 overflow-hidden" style="border-radius: 2px;">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800 text-left">
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">KLIENT</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">POJAZD</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">USŁUGA</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">TERMIN</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">PRACOWNIK</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">STATUS</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rezerwacje as $r)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-zinc-200">{{ $r->klient->imie }} {{ $r->klient->nazwisko }}</td>
                    <td class="px-4 py-3 text-zinc-400">{{ $r->pojazd->marka }} {{ $r->pojazd->model }}</td>
                    <td class="px-4 py-3 text-zinc-400">{{ $r->usluga->nazwa }}</td>
                    <td class="px-4 py-3 text-zinc-400">{{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $r->pracownik ? $r->pracownik->imie . ' ' . $r->pracownik->nazwisko : '—' }}</td>
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
                            {{ mb_strtoupper($r->status, 'UTF-8') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.reservations.destroy', $r) }}" class="inline"
                              onsubmit="return confirm('Na pewno trwale usunąć tę rezerwację? Tej operacji nie można cofnąć.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-400">Usuń</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-zinc-500">Brak rezerwacji w tej kategorii.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $rezerwacje->links() }}
</div>
@endsection