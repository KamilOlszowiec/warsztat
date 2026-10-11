@extends('layouts.pracownik')
@section('title', 'Przydział')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">PRZYDZIAŁ</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-2">
    {{ $rezerwacja->klient->imie }} {{ $rezerwacja->klient->nazwisko }} — {{ $rezerwacja->nazwy_uslug }}
</h1>
<p class="text-sm text-zinc-400 mb-6">
    Termin:
    <span class="text-zinc-200 font-mono">
        {{ $rezerwacja->termin_rozpoczecia->format('d.m.Y H:i') }}–{{ $rezerwacja->termin_zakonczenia->format('H:i') }}
    </span>
    · Pojazd: <span class="text-zinc-200">{{ $rezerwacja->pojazd->marka }} {{ $rezerwacja->pojazd->model }}</span>
</p>

@php
    $obecneStanowiskoDostepne = $stanowiska->contains('id_stanowiska', $rezerwacja->id_stanowiska);
    $obecnyMechanikDostepny = $mechanicy->contains('id_uzytkownika', $rezerwacja->id_mechanika);
@endphp

<form method="POST" action="{{ route('pracownik.reservations.przydzial.update', $rezerwacja) }}"
      class="bg-[#1F2128] border border-zinc-800 p-6 max-w-md space-y-4" style="border-radius: 2px;">
    @csrf
    @method('PUT')

    @error('przydzial') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

    {{-- Stanowisko --}}
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">STANOWISKO</label>
        @if ($stanowiska->isEmpty())
            <p class="text-sm text-red-400">Brak wolnego stanowiska w terminie tej wizyty. Zmień termin rezerwacji.</p>
        @else
            <select name="id_stanowiska"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                @foreach ($stanowiska as $s)
                    <option value="{{ $s->id_stanowiska }}"
                        {{ (string) old('id_stanowiska', $rezerwacja->id_stanowiska) === (string) $s->id_stanowiska ? 'selected' : '' }}>
                        {{ $s->nazwa }} ({{ $s->typ_etykieta }}){{ $s->id_stanowiska == $rezerwacja->id_stanowiska ? ' – obecne' : '' }}
                    </option>
                @endforeach
            </select>
            @if ($rezerwacja->id_stanowiska && !$obecneStanowiskoDostepne)
                <p class="text-xs text-yellow-400 mt-1">Obecne stanowisko ({{ $rezerwacja->stanowisko->nazwa ?? '—' }}) jest nieaktywne lub zajęte – wybierz inne.</p>
            @endif
        @endif
        @error('id_stanowiska') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Mechanik --}}
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">MECHANIK</label>
        @if ($mechanicy->isEmpty())
            <p class="text-sm text-red-400">Żaden mechanik nie ma wolnej zmiany obejmującej tę wizytę. Zmień termin albo grafik.</p>
        @else
            <select name="id_mechanika"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                @foreach ($mechanicy as $m)
                    <option value="{{ $m->id_uzytkownika }}"
                        {{ (string) old('id_mechanika', $rezerwacja->id_mechanika) === (string) $m->id_uzytkownika ? 'selected' : '' }}>
                        {{ $m->imie }} {{ $m->nazwisko }}{{ $m->id_uzytkownika == $rezerwacja->id_mechanika ? ' – obecny' : '' }}
                    </option>
                @endforeach
            </select>
            @if ($rezerwacja->id_mechanika && !$obecnyMechanikDostepny)
                <p class="text-xs text-yellow-400 mt-1">
                    Obecny mechanik ({{ $rezerwacja->mechanik ? $rezerwacja->mechanik->imie . ' ' . $rezerwacja->mechanik->nazwisko : '—' }})
                    nie ma zmiany w tym terminie lub jest zajęty – wybierz innego.
                </p>
            @endif
        @endif
        @error('id_mechanika') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-3">
        @if ($stanowiska->isNotEmpty() && $mechanicy->isNotEmpty())
            <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
                Zapisz przydział
            </button>
        @else
            <a href="{{ route('pracownik.reservations.edit-termin', $rezerwacja) }}"
               class="flex-1 text-center bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
                Zmień termin
            </a>
        @endif
        <a href="{{ route('pracownik.reservations.index') }}"
           class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
            Anuluj
        </a>
    </div>
</form>
@endsection