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

<form id="formularz-rezerwacji" method="POST" action="{{ route('klient.reservations.store') }}"
      class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg space-y-5" style="border-radius: 2px;">
    @csrf

    {{-- Usługi --}}
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-2">USŁUGI (MOŻESZ WYBRAĆ KILKA)</label>
        <div class="space-y-2">
            @foreach ($uslugi as $u)
                <label class="block cursor-pointer">
                    <input type="checkbox" name="uslugi[]" value="{{ $u->id_uslugi }}"
                           data-czas="{{ $u->czas_trwania }}" data-cena="{{ $u->cena }}"
                           class="peer sr-only"
                           {{ in_array($u->id_uslugi, old('uslugi', [])) ? 'checked' : '' }}>
                    <div class="flex items-center justify-between px-3 py-2.5 border border-zinc-700 text-zinc-300 peer-checked:border-orange-600 peer-checked:bg-orange-600/10 peer-checked:text-zinc-100 transition" style="border-radius: 2px;">
                        <span class="font-medium">{{ $u->nazwa }}</span>
                        <span class="text-xs font-mono text-zinc-400">{{ $u->czas_trwania }} min · {{ number_format($u->cena, 2) }} zł</span>
                    </div>
                </label>
            @endforeach
        </div>
        @error('uslugi') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror
        @if ($errors->has('uslugi.*')) <p class="text-sm text-red-500 mt-2">{{ $errors->first('uslugi.*') }}</p> @endif
    </div>

    {{-- Podsumowanie --}}
    <div class="flex justify-between text-sm border-t border-zinc-800 pt-4">
        <span class="text-zinc-400">Szacowany czas: <span id="suma-czas" class="text-zinc-100 font-mono">—</span></span>
        <span class="text-zinc-400">Cena: <span id="suma-cena" class="text-zinc-100 font-mono">—</span></span>
    </div>

    {{-- Data --}}
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">DATA</label>
        <input type="date" id="pole-data" name="data" min="{{ now()->format('Y-m-d') }}" value="{{ old('data') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('data') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Godziny --}}
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-2">DOSTĘPNE GODZINY</label>
        <p id="sloty-komunikat" class="text-zinc-500 text-sm">Zaznacz usługi i wybierz datę, aby zobaczyć wolne terminy.</p>
        <div id="sloty" class="grid grid-cols-4 gap-2"></div>
        @error('godzina') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror
    </div>

    {{-- Pojazd --}}
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">POJAZD</label>
        <select name="pojazd_id" class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            @foreach ($pojazdy as $p)
                <option value="{{ $p->id_pojazdu }}" {{ (string) old('pojazd_id') === (string) $p->id_pojazdu ? 'selected' : '' }}>
                    {{ $p->marka }} {{ $p->model }} ({{ $p->numer_rejestracyjny }})
                </option>
            @endforeach
        </select>
        @error('pojazd_id') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Uwagi --}}
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">UWAGI (OPCJONALNIE)</label>
        <textarea name="uwagi" rows="2" class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">{{ old('uwagi') }}</textarea>
        @error('uwagi') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="submit" id="przycisk-rezerwuj" disabled
        class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-orange-600" style="border-radius: 2px;">
        Zarezerwuj wizytę
    </button>
</form>

<script>
(function () {
    const form = document.getElementById('formularz-rezerwacji');
    const checkboxy = form.querySelectorAll('input[name="uslugi[]"]');
    const poleData = document.getElementById('pole-data');
    const kontener = document.getElementById('sloty');
    const komunikat = document.getElementById('sloty-komunikat');
    const sumaCzas = document.getElementById('suma-czas');
    const sumaCena = document.getElementById('suma-cena');
    const przycisk = document.getElementById('przycisk-rezerwuj');

    const url = @json(route('klient.reservations.availability'));
    let poprzedniaGodzina = @json(old('godzina'));
    let numerZapytania = 0;

    function zaznaczone() {
        return Array.from(checkboxy).filter(c => c.checked);
    }

    function formatCzas(minuty) {
        if (minuty < 60) return minuty + ' min';
        const h = Math.floor(minuty / 60);
        const m = minuty % 60;
        return h + ' h' + (m ? ' ' + m + ' min' : '');
    }

    function odswiezPodsumowanie() {
        const wybrane = zaznaczone();
        if (wybrane.length === 0) {
            sumaCzas.textContent = '—';
            sumaCena.textContent = '—';
            return;
        }
        const czas = wybrane.reduce((s, c) => s + parseInt(c.dataset.czas, 10), 0);
        const cena = wybrane.reduce((s, c) => s + parseFloat(c.dataset.cena), 0);
        sumaCzas.textContent = formatCzas(czas);
        sumaCena.textContent = cena.toFixed(2) + ' zł';
    }

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
            if (godzina === poprzedniaGodzina) input.checked = true;

            const kafelek = document.createElement('div');
            kafelek.className = 'text-center text-sm py-2 border border-zinc-700 text-zinc-300 peer-checked:bg-orange-600 peer-checked:border-orange-600 peer-checked:text-white transition';
            kafelek.style.borderRadius = '2px';
            kafelek.textContent = godzina;

            label.appendChild(input);
            label.appendChild(kafelek);
            kontener.appendChild(label);
        });

        poprzedniaGodzina = null;
        przycisk.disabled = !kontener.querySelector('input[name="godzina"]:checked');
    }

    async function pobierzGodziny() {
        const wybrane = zaznaczone();

        if (wybrane.length === 0 || !poleData.value) {
            pokazKomunikat('Zaznacz usługi i wybierz datę, aby zobaczyć wolne terminy.');
            return;
        }

        const params = new URLSearchParams();
        wybrane.forEach(c => params.append('uslugi[]', c.value));
        params.append('data', poleData.value);

        const numer = ++numerZapytania;
        pokazKomunikat('Szukam wolnych terminów…');

        try {
            const odp = await fetch(url + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const dane = await odp.json();

            // Ignorujemy odpowiedź, jeśli w międzyczasie użytkownik zmienił wybór
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

    checkboxy.forEach(c => c.addEventListener('change', function () {
        odswiezPodsumowanie();
        pobierzGodziny();
    }));
    poleData.addEventListener('change', pobierzGodziny);
    kontener.addEventListener('change', function () {
        przycisk.disabled = !kontener.querySelector('input[name="godzina"]:checked');
    });

    odswiezPodsumowanie();
    if (zaznaczone().length > 0 && poleData.value) {
        pobierzGodziny();
    }
})();
</script>

@endif
@endsection