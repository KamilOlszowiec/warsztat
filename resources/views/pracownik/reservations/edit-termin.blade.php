{{-- resources/views/pracownik/reservations/edit-termin.blade.php --}}
@extends('layouts.pracownik')
@section('title', 'Zmień termin')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">ZMIANA TERMINU</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">
    {{ $rezerwacja->klient->imie }} {{ $rezerwacja->klient->nazwisko }} — {{ $rezerwacja->usluga->nazwa }}
</h1>

<form method="POST" action="{{ route('pracownik.reservations.update-termin', $rezerwacja) }}" class="bg-[#1F2128] border border-zinc-800 p-6 max-w-md space-y-4" style="border-radius: 2px;">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">DATA</label>
        <input type="date" name="data" value="{{ old('data', $rezerwacja->termin_rozpoczecia->format('Y-m-d')) }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('data') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">GODZINA</label>
        <input type="time" name="godzina" value="{{ old('godzina', $rezerwacja->termin_rozpoczecia->format('H:i')) }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('godzina') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-3">
        <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
            Zapisz nowy termin
        </button>
        <a href="{{ route('pracownik.reservations.index') }}"
           class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
            Anuluj
        </a>
    </div>
</form>
@endsection