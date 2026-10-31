@extends('layouts.klient')
@section('title', 'Zmień termin')

@section('content')
@php
    $r = $rezerwacja;
    $wybranyMechanik = old('id_mechanika', $domyslnyMechanik);
@endphp

<div class="max-w-4xl">
    <a href="{{ route('klient.reservations.show', $r) }}" class="inline-block text-sm text-zinc-400 hover:text-zinc-200 mb-4">← Szczegóły rezerwacji</a>

    <p class="text-sm font-mono text-orange-500 mb-1">ZMIANA TERMINU</p>
    <h1 class="text-2xl font-bold text-zinc-50 mb-2">{{ $r->nazwy_uslug }}</h1>
    <p class="text-sm text-zinc-400 mb-2">
        Obecny termin:
        <span class="text-zinc-200 font-mono">{{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}–{{ $r->termin_zakonczenia->format('H:i') }}</span>
        · Pojazd: <span class="text-zinc-200">{{ $r->pojazd->marka }} {{ $r->pojazd->model }}</span>
        · Czas: <span class="text-zinc-200 font-mono">{{ $r->laczny_czas }} min</span>
    </p>
    <p class="text-sm text-yellow-400 mb-6">
        Po zmianie terminu rezerwacja wróci do statusu „oczekująca” i warsztat potwierdzi ją ponownie.
    </p>

    <form id="formularz-terminu" method="POST" action="{{ route('klient.reservations.update-termin', $r) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="data" id="pole-data" value="{{ old('data', $r->termin_rozpoczecia->toDateString()) }}">

        @error('error') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

        <div class="max-w-sm">
            <label for="pole-mechanik" class="block text-xs font-mono text-zinc-400 mb-1.5">MECHANIK</label>
            <select id="pole-mechanik" name="id_mechanika"
                    class="w-full bg-[#1F2128] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                <option value="">Dowolny mechanik (najwcześniejszy termin)</option>
                @foreach ($mechanicy as $m)
                    <option value="{{ $m['id'] }}" {{ (string) $wybranyMechanik === (string) $m['id'] ? 'selected' : '' }}>
                        {{ $m['nazwa'] }}{{ $m['id'] === (int) $r->id_mechanika ? ' (obecny)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,300px)_1fr] gap-6">
            <x-kalendarz id="kalendarz" />
            <div>
                <x-siatka-godzin id="godziny" />
                @error('data') <p class="text-sm text-red-500 mt-3">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-3 max-w-md">
            <button type="submit" id="przycisk-zapisz" disabled
                class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-orange-600" style="border-radius: 2px;">
                Zapisz nowy termin
            </button>
            <a href="{{ route('klient.reservations.show', $r) }}"
               class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
                Wróć
            </a>
        </div>
    </form>
</div>

@vite('resources/js/rezerwacje.js')
<script type="module">
const { format, kalendarz, siatkaGodzin } = window.Rezerwacje;

const poleData = document.getElementById('pole-data');
const poleMechanik = document.getElementById('pole-mechanik');
const przycisk = document.getElementById('przycisk-zapisz');
const obecnaData = @json($r->termin_rozpoczecia->toDateString());
const obecnaGodzina = @json($r->termin_rozpoczecia->format('H:i'));

const godziny = siatkaGodzin(document.getElementById('godziny'), {
    url: @json(route('klient.reservations.availability-for', $r)),
    zaznacz: @json(old('godzina')),
    opis: () => (poleData.value ? format.data(poleData.value) + ' · czas wizyty: ' + format.czas(@json($r->laczny_czas)) : ''),
    parametry: () => {
        if (!poleData.value) return 'Wybierz dzień w kalendarzu.';
        const p = new URLSearchParams({ data: poleData.value });
        if (poleMechanik.value) p.append('mechanik', poleMechanik.value);
        return p;
    },
    // Obecny termin wizyty oznaczony kropką
    dopisek: g => (poleData.value === obecnaData && g === obecnaGodzina ? ' •' : ''),
    poZmianie: () => { przycisk.disabled = !godziny.wybrana(); },
});

kalendarz(document.getElementById('kalendarz'), poleData, {
    dzisiaj: @json(now()->toDateString()),
    maksData: @json($maksData),
    dniZamkniete: @json($dniZamkniete),
    dniWolne: @json($dniWolne),
    poWyborze: () => godziny.pobierz(),
});

poleMechanik.addEventListener('change', () => godziny.pobierz());
godziny.pobierz();
</script>
@endsection
