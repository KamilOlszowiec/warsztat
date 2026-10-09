{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')
@section('title', 'Panel główny')

@section('content')
@php
    $liczbaUzytkownikow = \App\Models\Uzytkownik::count();
    $liczbaAktywnychUslug = \App\Models\Usluga::where('czy_aktywna', true)->count();
    $rezerwacjeDzis = \App\Models\Rezerwacja::whereDate('termin_rozpoczecia', today())->count();
    $ostatnieUslugi = \App\Models\Usluga::orderByDesc('created_at')->take(5)->get();
@endphp

<div class="mb-8">
    <p class="text-sm font-mono text-orange-500 mb-1">PANEL GŁÓWNY</p>
    <h1 class="text-2xl font-bold text-zinc-50">Witaj, {{ auth()->user()->imie }}</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-[#1F2128] border-l-4 border-orange-600 p-5" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-1">UŻYTKOWNICY</p>
        <p class="text-3xl font-bold text-zinc-50">{{ $liczbaUzytkownikow }}</p>
    </div>
    <div class="bg-[#1F2128] border-l-4 border-orange-600 p-5" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-1">AKTYWNE USŁUGI</p>
        <p class="text-3xl font-bold text-zinc-50">{{ $liczbaAktywnychUslug }}</p>
    </div>
    <div class="bg-[#1F2128] border-l-4 border-orange-600 p-5" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-1">REZERWACJE DZIŚ</p>
        <p class="text-3xl font-bold text-zinc-50">{{ $rezerwacjeDzis }}</p>
    </div>
</div>

<div class="bg-[#1F2128] border border-zinc-800 overflow-hidden max-w-2xl" style="border-radius: 2px;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
        <p class="text-sm font-mono text-zinc-400">OSTATNIO DODANE USŁUGI</p>
        <a href="{{ route('admin.services.index') }}" class="text-orange-500 hover:text-orange-400 text-sm font-medium">
            Zobacz wszystkie →
        </a>
    </div>

    @forelse ($ostatnieUslugi as $usluga)
        <div class="flex items-center justify-between px-5 py-3 {{ !$loop->last ? 'border-b border-zinc-800/50' : '' }}">
            <div>
                <p class="text-zinc-200 text-sm font-medium">{{ $usluga->nazwa }}</p>
                <p class="text-zinc-500 text-xs">{{ number_format($usluga->cena, 2) }} zł · {{ $usluga->czas_trwania }} min</p>
            </div>
            @if ($usluga->czy_aktywna)
                <span class="text-xs font-mono px-2 py-1 bg-green-900/30 text-green-400" style="border-radius: 2px;">AKTYWNA</span>
            @else
                <span class="text-xs font-mono px-2 py-1 bg-zinc-800 text-zinc-500" style="border-radius: 2px;">NIEAKTYWNA</span>
            @endif
        </div>
    @empty
        <p class="px-5 py-6 text-center text-zinc-500 text-sm">Brak dodanych usług.</p>
    @endforelse
</div>
@endsection