@extends('layouts.klient')
@section('title', 'Nowa rezerwacja')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">NOWA REZERWACJA</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Umów wizytę</h1>

@if ($pojazdy->isEmpty())
    <div class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg" style="border-radius: 2px;">
        <p class="text-zinc-400 mb-4">Musisz najpierw dodać pojazd, zanim umówisz wizytę.</p>
        <a href="{{ route('klient.vehicles.create') }}" class="text-orange-500 hover:text-orange-400 font-medium">
            Dodaj pojazd →
        </a>
    </div>
@else

{{-- Krok 1: wybór usługi i daty --}}
<form method="GET" action="{{ route('klient.reservations.create') }}"
      class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg space-y-4 mb-6" style="border-radius: 2px;">

    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">USŁUGA</label>
        <select name="usluga_id" class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            <option value="">— wybierz usługę —</option>
            @foreach ($uslugi as $u)
                <option value="{{ $u->id_uslugi }}" {{ (string) request('usluga_id') === (string) $u->id_uslugi ? 'selected' : '' }}>
                    {{ $u->nazwa }} ({{ $u->czas_trwania }} min, {{ number_format($u->cena, 2) }} zł)
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">DATA</label>
        <input type="date" name="data" min="{{ now()->format('Y-m-d') }}" value="{{ $wybranaData }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    </div>

    <button type="submit" class="w-full border border-orange-600 text-orange-500 hover:bg-orange-600 hover:text-white font-semibold py-2.5 transition" style="border-radius: 2px;">
        Sprawdź dostępne terminy
    </button>
</form>

{{-- Krok 2: wybór godziny i pojazdu, jeśli usługa+data wybrane --}}
@if ($wybranaUsluga && $wybranaData)
    <div class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg" style="border-radius: 2px;">
        <p class="text-sm text-zinc-400 mb-4">
            <span class="text-zinc-200 font-medium">{{ $wybranaUsluga->nazwa }}</span> —
            {{ \Carbon\Carbon::parse($wybranaData)->translatedFormat('d.m.Y') }}
        </p>

        @if (empty($sloty))
            <p class="text-zinc-500">Brak wolnych terminów tego dnia. Wybierz inną datę.</p>
        @else
            <form method="POST" action="{{ route('klient.reservations.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="usluga_id" value="{{ $wybranaUsluga->id_uslugi }}">
                <input type="hidden" name="data" value="{{ $wybranaData }}">

                <div>
                    <label class="block text-xs font-mono text-zinc-400 mb-2">DOSTĘPNE GODZINY</label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($sloty as $slot)
                            <label class="cursor-pointer">
                                <input type="radio" name="godzina" value="{{ $slot }}" class="peer sr-only" required>
                                <div class="text-center text-sm py-2 border border-zinc-700 text-zinc-300 peer-checked:bg-orange-600 peer-checked:border-orange-600 peer-checked:text-white transition" style="border-radius: 2px;">
                                    {{ $slot }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('godzina') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-mono text-zinc-400 mb-1.5">POJAZD</label>
                    <select name="pojazd_id" class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                        @foreach ($pojazdy as $p)
                            <option value="{{ $p->id_pojazdu }}">{{ $p->marka }} {{ $p->model }} ({{ $p->numer_rejestracyjny }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono text-zinc-400 mb-1.5">UWAGI (OPCJONALNIE)</label>
                    <textarea name="uwagi" rows="2" class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;"></textarea>
                </div>

                <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
                    Zarezerwuj wizytę
                </button>
            </form>
        @endif
    </div>
@endif

@endif
@endsection