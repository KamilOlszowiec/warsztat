@extends('layouts.klient')
@section('title', 'Dodaj pojazd')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">NOWY POJAZD</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Dodaj pojazd</h1>

<form method="POST" action="{{ route('klient.vehicles.store') }}" class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg space-y-4" style="border-radius: 2px;">
    @csrf
    @include('klient.vehicles._form')

    <div class="flex gap-3">
        <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
            Dodaj pojazd
        </button>
        <a href="{{ route('klient.vehicles.index') }}"
           class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
            Anuluj
        </a>
    </div>
</form>
@endsection