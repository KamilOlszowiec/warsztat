@extends('layouts.klient')
@section('title', 'Zmień termin')

@section('content')
@php $r = $rezerwacja; @endphp

<div class="max-w-4xl">
    <a href="{{ route('klient.reservations.show', $r) }}" class="inline-block text-sm text-zinc-400 hover:text-zinc-200 mb-4">← Szczegóły rezerwacji</a>

    <p class="text-sm font-mono text-orange-500 mb-1">ZMIANA TERMINU</p>
    <h1 class="text-2xl font-bold text-zinc-50 mb-2">{{ $r->nazwy_uslug }}</h1>
    <p class="text-sm text-zinc-400 mb-2">
        Obecny termin:
        <span class="text-zinc-200 font-mono">
            {{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}–{{ $r->termin_zakonczenia->format('H:i') }}
        </span>
        · Pojazd: <span class="text-zinc-200">{{ $r->pojazd->marka }} {{ $r->pojazd->model }}</span>
        · Czas: <span class="text-zinc-200 font-mono">{{ $r->laczny_czas }} min</span>
    </p>
    <p class="text-sm text-yellow-400 mb-6">
        Po zmianie terminu rezerwacja wróci do statusu „oczekująca” i warsztat potwierdzi ją ponownie.
    </p>

    <form id="formularz-terminu" method="POST" action="{{ route('klient.reservations.update-termin', $r) }}"
          class="bg-[#1F2128] border border-zinc-800 p-6 max-w-md space-y-4" style="border-radius: 2px;">
        @csrf
        @method('PUT')

        @error('error') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

        <div>
            <label for="pole-mechanik" class="block text-xs font-mono text-zinc-400 mb-1.5">MECHANIK</label>
            @php $wybranyMechanik = old('id_mechanika', $domyslnyMechanik); @endphp
            <select id="pole-mechanik" name="id_mechanika"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                <option value="">Dowolny mechanik (najwcześniejszy termin)</option>
                @foreach ($mechanicy as $m)
                    <option value="{{ $m['id'] }}" {{ (string) $wybranyMechanik === (string) $m['id'] ? 'selected' : '' }}>
                        {{ $m['nazwa'] }}{{ $m['id'] === (int) $r->id_mechanika ? ' (obecny)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="pole-data" class="block text-xs font-mono text-zinc-400 mb-1.5">DATA</label>
            <input type="date" id="pole-data" name="data"
                min="{{ now()->format('Y-m-d') }}" max="{{ $maksData }}"
                value="{{ old('data', $r->termin_rozpoczecia->format('Y-m-d')) }}"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            @error('data') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-mono text-zinc-400 mb-2">DOSTĘPNE GODZINY</label>
            <p id="sloty-komunikat" class="text-zinc-500 text-sm">Wybierz datę, aby zobaczyć wolne terminy.</p>
            <div id="sloty" class="grid grid-cols-4 gap-2"></div>
            @error('godzina') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3">
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

<script>
(function () {
    const poleData = document.getElementById('pole-data');
    const kontener = document.getElementById('sloty');
    const komunikat = document.getElementById('sloty-komunikat');
    const przycisk = document.getElementById('przycisk-zapisz');
    const poleMechanik = document.getElementById('pole-mechanik');

    const url = @json(route('klient.reservations.availability-for', $r));
    const dniZamkniete = @json($dniZamkniete);
    const dniWolne = @json($dniWolne);
    const obecnaData = @json($r->termin_rozpoczecia->format('Y-m-d'));
    const obecnaGodzina = @json($r->termin_rozpoczecia->format('H:i'));
    let zaznaczGodzine = @json(old('godzina'));
    let numerZapytania = 0;

    function pokazKomunikat(tekst) {
        kontener.innerHTML = '';
        komunikat.textContent = tekst;
        komunikat.classList.remove('hidden');
        przycisk.disabled = true;
    }

    // Dzień tygodnia w numeracji ISO: 1 = poniedziałek, 7 = niedziela
    function dzienTygodniaIso(data) {
        const dzien = new Date(data + 'T12:00:00').getDay();
        return dzien === 0 ? 7 : dzien;
    }

    function renderujGodziny(godziny) {
        kontener.innerHTML = '';
        komunikat.classList.add('hidden');

        godziny.forEach(function (godzina) {
            const label = document.createElement('label');
            label.className = 'cursor-pointer';

            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'godzina';
            input.value = godzina;
            input.required = true;
            input.className = 'peer sr-only';
            if (godzina === zaznaczGodzine) input.checked = true;

            const kafelek = document.createElement('div');
            kafelek.className = 'text-center text-sm py-2 border border-zinc-700 text-zinc-300 peer-checked:bg-orange-600 peer-checked:border-orange-600 peer-checked:text-white transition';
            kafelek.style.borderRadius = '2px';
            kafelek.textContent = godzina;

            // Obecna godzina rezerwacji oznaczona kropką
            if (poleData.value === obecnaData && godzina === obecnaGodzina) {
                kafelek.textContent = godzina + ' •';
                kafelek.title = 'Obecny termin';
            }

            label.appendChild(input);
            label.appendChild(kafelek);
            kontener.appendChild(label);
        });

        zaznaczGodzine = null;
        przycisk.disabled = !kontener.querySelector('input[name="godzina"]:checked');
    }

    async function pobierzGodziny() {
        if (!poleData.value) {
            pokazKomunikat('Wybierz datę, aby zobaczyć wolne terminy.');
            return;
        }

        if (dniZamkniete.includes(dzienTygodniaIso(poleData.value)) || dniWolne.includes(poleData.value)) {
            pokazKomunikat('Warsztat jest w tym dniu zamknięty. Wybierz inny dzień.');
            return;
        }

        const numer = ++numerZapytania;
        pokazKomunikat('Szukam wolnych terminów…');

        try {
            const params = new URLSearchParams({ data: poleData.value });
            if (poleMechanik.value) params.append('mechanik', poleMechanik.value);

            const odp = await fetch(url + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const dane = await odp.json();

            if (numer !== numerZapytania) return;

            if (!odp.ok) {
                const bledy = dane.errors ? Object.values(dane.errors).flat() : [];
                pokazKomunikat(bledy[0] || 'Nie udało się pobrać terminów. Spróbuj ponownie.');
                return;
            }

            if (dane.godziny.length === 0) {
                pokazKomunikat(dane.komunikat || 'Brak wolnych terminów w wybranym dniu.');
                return;
            }

            renderujGodziny(dane.godziny);
        } catch (e) {
            if (numer === numerZapytania) {
                pokazKomunikat('Nie udało się pobrać terminów. Sprawdź połączenie i spróbuj ponownie.');
            }
        }
    }

    poleData.addEventListener('change', pobierzGodziny);
    poleMechanik.addEventListener('change', pobierzGodziny);
    kontener.addEventListener('change', function () {
        przycisk.disabled = !kontener.querySelector('input[name="godzina"]:checked');
    });

    pobierzGodziny();
})();
</script>
@endsection
