@extends('layouts.pracownik')
@section('title', 'Klienci')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">KLIENCI</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Klienci i ich pojazdy</h1>

<div class="space-y-4">
    @forelse ($klienci as $klient)
        <div class="bg-[#1F2128] border border-zinc-800 p-5" style="border-radius: 2px;">
            <div class="mb-3">
                <p class="text-zinc-100 font-semibold">{{ $klient->imie }} {{ $klient->nazwisko }}</p>
                <p class="text-zinc-500 text-sm">{{ $klient->email }} @if($klient->telefon) · {{ $klient->telefon }} @endif</p>
            </div>

            @if ($klient->pojazdy->isEmpty())
                <p class="text-zinc-600 text-sm">Brak dodanych pojazdów.</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach ($klient->pojazdy as $pojazd)
                        <div class="bg-[#15161A] px-3 py-2 text-sm" style="border-radius: 2px;">
                            <p class="text-zinc-200">{{ $pojazd->marka }} {{ $pojazd->model }}</p>
                            <p class="text-zinc-500 text-xs font-mono">{{ $pojazd->numer_rejestracyjny }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p class="text-zinc-500">Brak zarejestrowanych klientów.</p>
    @endforelse
</div>
@endsection