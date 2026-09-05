{{-- resources/views/pracownik/dashboard.blade.php --}}
@extends('layouts.pracownik')
@section('title', 'Panel główny')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">PANEL GŁÓWNY</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Witaj, {{ auth()->user()->imie }}</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <a href="{{ route('pracownik.reservations.index', ['status' => 'dzisiaj']) }}"
       class="bg-[#1F2128] border-l-4 border-orange-600 hover:bg-white/[0.02] p-5 transition" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-1">REZERWACJE DZIŚ</p>
        <p class="text-3xl font-bold text-zinc-50">{{ $dzisiaj }}</p>
    </a>
    <a href="{{ route('pracownik.reservations.index', ['status' => 'oczekująca']) }}"
       class="bg-[#1F2128] border-l-4 border-yellow-500 hover:bg-white/[0.02] p-5 transition" style="border-radius: 2px;">
        <p class="text-xs font-mono text-zinc-500 mb-1">OCZEKUJĄCE NA ZATWIERDZENIE</p>
        <p class="text-3xl font-bold text-zinc-50">{{ $oczekujace }}</p>
    </a>
</div>
@endsection