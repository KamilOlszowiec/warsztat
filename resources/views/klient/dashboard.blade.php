{{-- resources/views/klient/dashboard.blade.php --}}
@extends('layouts.klient')
@section('title', 'Panel klienta')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">PANEL GŁÓWNY</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Witaj, {{ auth()->user()->imie }}</h1>

@php
    $nastepnaRezerwacja = \App\Models\Rezerwacja::where('id_klienta', auth()->id())
        ->whereIn('status', ['oczekująca', 'zatwierdzona'])
        ->where('termin_rozpoczecia', '>=', now())
        ->with(['usluga', 'pojazd'])
        ->orderBy('termin_rozpoczecia')
        ->first();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-[#1F2128] border-l-4 border-orange-600 p-5" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-1">TWOJE POJAZDY</p>
        <p class="text-3xl font-bold text-zinc-50">{{ auth()->user()->pojazdy()->count() }}</p>
    </div>

    <a href="{{ route('klient.reservations.create') }}"
       class="bg-orange-600 hover:bg-orange-700 transition p-5 flex flex-col justify-center" style="border-radius: 2px;">
        <p class="text-white font-semibold">+ Umów nową wizytę</p>
    </a>
</div>

<div class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg" style="border-radius: 2px;">
    <p class="text-xs font-mono text-zinc-500 mb-3">NAJBLIŻSZA REZERWACJA</p>

    @if ($nastepnaRezerwacja)
        <p class="text-zinc-200 font-medium mb-1">{{ $nastepnaRezerwacja->usluga->nazwa }}</p>
        <p class="text-zinc-400 text-sm mb-1">{{ $nastepnaRezerwacja->pojazd->marka }} {{ $nastepnaRezerwacja->pojazd->model }}</p>
        <p class="text-zinc-400 text-sm mb-3">{{ $nastepnaRezerwacja->termin_rozpoczecia->format('d.m.Y H:i') }}</p>
        <a href="{{ route('klient.reservations.index') }}" class="text-orange-500 hover:text-orange-400 text-sm font-medium">
            Zobacz wszystkie rezerwacje →
        </a>
    @else
        <p class="text-zinc-500">Nie masz żadnych nadchodzących rezerwacji.</p>
    @endif
</div>
@endsection