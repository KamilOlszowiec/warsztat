{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')
@section('title', 'Panel główny')

@section('content')
@php
    $liczbaUzytkownikow = \App\Models\Uzytkownik::count();
    $liczbaAktywnychUslug = \App\Models\Usluga::where('czy_aktywna', true)->count();
    $rezerwacjeDzis = \App\Models\Rezerwacja::whereDate('termin_rozpoczecia', today())->count();
@endphp

<div class="mb-8">
    <p class="text-sm font-mono text-orange-500 mb-1">PANEL GŁÓWNY</p>
    <h1 class="text-2xl font-bold text-zinc-50">Witaj, {{ auth()->user()->imie }}</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
@endsection