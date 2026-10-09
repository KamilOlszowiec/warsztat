@extends('layouts.klient')
@section('title', 'Panel klienta')

@section('content')
@php
    $nastepnaRezerwacja = \App\Models\Rezerwacja::where('id_klienta', auth()->id())
        ->whereIn('status', ['oczekująca', 'zatwierdzona'])
        ->where('termin_rozpoczecia', '>=', now())
        ->with(['usluga', 'pojazd'])
        ->orderBy('termin_rozpoczecia')
        ->first();

    $liczbaPojazdow = auth()->user()->pojazdy()->count();
    $liczbaAktywnychRezerwacji = \App\Models\Rezerwacja::where('id_klienta', auth()->id())
        ->whereIn('status', ['oczekująca', 'zatwierdzona'])
        ->count();
@endphp

<div class="mb-8">
    <p class="text-sm font-mono text-orange-500 mb-1">PANEL GŁÓWNY</p>
    <h1 class="text-3xl font-extrabold text-zinc-50">Witaj, {{ auth()->user()->imie }} 👋</h1>
</div>

{{-- Hero: najbliższa rezerwacja --}}
<div class="mb-6 relative overflow-hidden" style="border-radius: 2px;">
    @if ($nastepnaRezerwacja)
        <div class="bg-gradient-to-br from-orange-600 to-orange-700 p-6 md:p-8">
            <p class="text-xs font-mono text-orange-100 mb-2 tracking-wide">NAJBLIŻSZA WIZYTA</p>
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-1">{{ $nastepnaRezerwacja->usluga->nazwa }}</h2>
                    <p class="text-orange-100 text-sm">
                        {{ $nastepnaRezerwacja->pojazd->marka }} {{ $nastepnaRezerwacja->pojazd->model }}
                        · {{ $nastepnaRezerwacja->termin_rozpoczecia->translatedFormat('d F, H:i') }}
                    </p>
                </div>
                <a href="{{ route('klient.reservations.index') }}"
                   class="shrink-0 bg-white text-orange-700 hover:bg-orange-50 font-semibold text-sm px-5 py-2.5 transition text-center" style="border-radius: 2px;">
                    Zobacz szczegóły
                </a>
            </div>
        </div>
    @else
        <div class="bg-[#1F2128] border border-zinc-800 p-6 md:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs font-mono text-zinc-500 mb-2 tracking-wide">NAJBLIŻSZA WIZYTA</p>
                <p class="text-zinc-300">Nie masz żadnych nadchodzących rezerwacji.</p>
            </div>
            <a href="{{ route('klient.reservations.create') }}"
               class="shrink-0 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-sm px-5 py-2.5 transition text-center" style="border-radius: 2px;">
                + Umów wizytę
            </a>
        </div>
    @endif
</div>

{{-- Statystyki --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-[#1F2128] border border-zinc-800 p-5" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-2">TWOJE POJAZDY</p>
        <p class="text-3xl font-bold text-zinc-50">{{ $liczbaPojazdow }}</p>
    </div>
    <div class="bg-[#1F2128] border border-zinc-800 p-5" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-2">AKTYWNE REZERWACJE</p>
        <p class="text-3xl font-bold text-zinc-50">{{ $liczbaAktywnychRezerwacji }}</p>
    </div>
    <a href="{{ route('klient.vehicles.create') }}"
       class="hidden md:flex bg-[#1F2128] border border-zinc-800 hover:border-orange-600 p-5 flex-col justify-center transition group" style="border-radius: 2px;">
        <p class="text-sm font-medium text-zinc-300 group-hover:text-orange-500 transition">+ Dodaj pojazd</p>
    </a>
</div>

{{-- Szybkie akcje --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <a href="{{ route('klient.reservations.create') }}"
       class="bg-[#1F2128] border-l-4 border-orange-600 hover:bg-white/[0.02] p-5 flex items-center justify-between transition group" style="border-radius: 2px;">
        <div>
            <p class="text-zinc-100 font-semibold">Umów nową wizytę</p>
            <p class="text-zinc-500 text-sm mt-0.5">Wybierz usługę, datę i godzinę</p>
        </div>
        <span class="text-orange-500 text-xl group-hover:translate-x-1 transition">→</span>
    </a>

    <a href="{{ route('klient.vehicles.index') }}"
       class="bg-[#1F2128] border-l-4 border-zinc-700 hover:bg-white/[0.02] p-5 flex items-center justify-between transition group" style="border-radius: 2px;">
        <div>
            <p class="text-zinc-100 font-semibold">Zarządzaj pojazdami</p>
            <p class="text-zinc-500 text-sm mt-0.5">Przegląd i historia serwisowa</p>
        </div>
        <span class="text-zinc-500 text-xl group-hover:translate-x-1 transition">→</span>
    </a>
</div>
@endsection