@extends('layouts.pracownik')
@section('title', 'Zmień termin')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">ZMIANA TERMINU</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-2">
    {{ $rezerwacja->klient->imie }} {{ $rezerwacja->klient->nazwisko }} — {{ $rezerwacja->nazwy_uslug }}
</h1>
<p class="text-sm text-zinc-400 mb-6">
    Obecny termin:
    <span class="text-zinc-200 font-mono">
        {{ $rezerwacja->termin_rozpoczecia->format('d.m.Y H:i') }}–{{ $rezerwacja->termin_zakonczenia->format('H:i') }}
    </span>
    · Mechanik: <span class="text-zinc-200">{{ $rezerwacja->mechanik ? $rezerwacja->mechanik->imie . ' ' . $rezerwacja->mechanik->nazwisko : '—' }}</span>
    · Czas: <span class="text-zinc-200 font-mono">{{ $rezerwacja->laczny_czas }} min</span>
</p>

<form id="formularz-terminu" method="POST" action="{{ route('pracownik.reservations.update-termin', $rezerwacja) }}"
      class="bg-[#1F2128] border border-zinc-800 p-6 max-w-md space-y-4" style="border-radius: 2px;">
    @csrf
    @method('PUT')

    @error('error') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">DATA</label>
        <input type="date" id="pole-data" name="data" min="{{ now()->format('Y-m-d') }}"
            value="{{ old('data', $rezerwacja->termin_rozpoczecia->format('Y-m-d')) }}"
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
        <a href="{{ route('pracownik.reservations.index') }}"
           class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
            Anuluj
        </a>
    </div>
</form>

<script>
(function () {
    const poleData = document.getElementById('pole-data');
    const kontener = document.getElementById('sloty');
    const komunikat = document.getElementById('sloty-komunikat');
    const przycisk = document.getElementById('przycisk-zapisz');

    const url = @json(route('pracownik.reservations.availability', $rezerwacja));
    const obecnaData = @json($rezerwacja->termin_rozpoczecia->format('Y-m-d'));
    const obecnaGodzina = @json($rezerwacja->termin_rozpoczecia->format('H:i'));
    let zaznaczGodzine = @json(old('godzina', $rezerwacja->termin_rozpoczecia->format('H:i')));
    let numerZapytania = 0;

    function pokazKomunikat(tekst) {
        kontener.innerHTML = '';
        komunikat.textContent = tekst;
        komunikat.classList.remove('hidden');
        przycisk.disabled = true;
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

        const numer = ++numerZapytania;
        pokazKomunikat('Szukam wolnych terminów…');

        try {
            const odp = await fetch(url + '?data=' + encodeURIComponent(poleData.value), {
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
    kontener.addEventListener('change', function () {
        przycisk.disabled = !kontener.querySelector('input[name="godzina"]:checked');
    });

    pobierzGodziny();
})();
</script>
@endsection