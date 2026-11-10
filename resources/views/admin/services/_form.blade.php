@php
    $s = $usluga ?? null;
    $specjalizacje = \App\Models\Specjalizacja::with(['pracownicy' => fn ($q) => $q->where('rola', 'pracownik')->orderBy('nazwisko')])
        ->orderBy('nazwa')
        ->get();

    $czas = (int) old('czas_trwania', $s->czas_trwania ?? 60);
    $czas = max(30, min(480, (int) (round($czas / 30) * 30)));
    $aktywna = (bool) old('czy_aktywna', $s->czy_aktywna ?? true);
    $wybranaSpec = (string) old('id_specjalizacji', $s->id_specjalizacji ?? '');

    // Kto ma daną specjalizację – do podpowiedzi pod listą
    $mechanicyWgSpec = $specjalizacje->mapWithKeys(fn ($spec) => [
        $spec->id_specjalizacji => $spec->pracownicy->map(fn ($p) => $p->imie . ' ' . $p->nazwisko)->values(),
    ]);

    $pole = 'w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600';
@endphp

@if ($errors->any())
    <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
        <p class="font-medium mb-1">Nie udało się zapisać usługi:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $blad)
                <li>{{ $blad }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label for="pole-nazwa" class="block text-xs font-mono text-zinc-400 mb-1.5">NAZWA USŁUGI</label>
    <input id="pole-nazwa" type="text" name="nazwa" value="{{ old('nazwa', $s->nazwa ?? '') }}" maxlength="100" required
        placeholder="np. Wymiana oleju" class="{{ $pole }}" style="border-radius: 2px;">
    @error('nazwa') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label for="pole-opis" class="block text-xs font-mono text-zinc-400 mb-1.5">OPIS (OPCJONALNIE)</label>
    <textarea id="pole-opis" name="opis" rows="2" placeholder="Jedno zdanie, co obejmuje usługa – klient zobaczy je w kreatorze rezerwacji"
        class="{{ $pole }}" style="border-radius: 2px;">{{ old('opis', $s->opis ?? '') }}</textarea>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- Cena --}}
    <div>
        <label for="pole-cena" class="block text-xs font-mono text-zinc-400 mb-1.5">CENA</label>
        <div class="relative">
            <input id="pole-cena" type="number" name="cena" step="0.01" min="0" inputmode="decimal" required
                value="{{ old('cena', $s->cena ?? '') }}" placeholder="0,00"
                class="{{ $pole }} pr-10" style="border-radius: 2px;">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-400 pointer-events-none">zł</span>
        </div>
        @error('cena') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <p id="etykieta-czas" class="block text-xs font-mono text-zinc-400 mb-1.5">CZAS TRWANIA</p>
        <input type="hidden" name="czas_trwania" id="pole-czas" value="{{ $czas }}">
        <div class="flex items-stretch h-[42px] border border-zinc-700 bg-[#15161A]" style="border-radius: 2px;" role="group" aria-labelledby="etykieta-czas">
            <button type="button" id="czas-minus" aria-label="Skróć o 30 minut"
                class="w-11 shrink-0 text-lg text-zinc-300 hover:text-white hover:bg-white/5 disabled:opacity-30 disabled:hover:bg-transparent border-r border-zinc-700">−</button>
            <output id="czas-opis" for="pole-czas" aria-live="polite" class="flex-1 flex items-center justify-center font-mono text-sm font-semibold text-zinc-50"></output>
            <button type="button" id="czas-plus" aria-label="Wydłuż o 30 minut"
                class="w-11 shrink-0 text-lg text-zinc-300 hover:text-white hover:bg-white/5 disabled:opacity-30 disabled:hover:bg-transparent border-l border-zinc-700">+</button>
        </div>
        <p class="text-xs text-zinc-500 mt-1">Co 30 minut, od 30 min do 8 h.</p>
        @error('czas_trwania') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label for="pole-spec" class="block text-xs font-mono text-zinc-400 mb-1.5">WYMAGANA SPECJALIZACJA</label>
    <select id="pole-spec" name="id_specjalizacji" required class="{{ $pole }}" style="border-radius: 2px;">
        <option value="">— wybierz —</option>
        @foreach ($specjalizacje as $spec)
            <option value="{{ $spec->id_specjalizacji }}" @selected($wybranaSpec === (string) $spec->id_specjalizacji)>{{ $spec->nazwa }}</option>
        @endforeach
    </select>
    <p id="spec-podpowiedz" class="text-xs mt-1.5 text-zinc-500">Usługę mogą wykonać tylko mechanicy z tą specjalizacją.</p>
    @error('id_specjalizacji') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<label class="flex items-center justify-between gap-4 px-4 py-3 border border-zinc-800 bg-[#18191D] cursor-pointer" style="border-radius: 2px;">
    <span>
        <span class="block text-sm font-medium text-zinc-100">Usługa aktywna</span>
        <span class="block text-xs text-zinc-400 mt-0.5">Widoczna dla klientów w kreatorze rezerwacji. Wyłączona trafia do zakładki „Ukryte”.</span>
    </span>
    <span class="relative shrink-0">
        <input type="checkbox" name="czy_aktywna" value="1" @checked($aktywna) class="peer sr-only">
        <span class="block w-10 h-6 bg-zinc-700 peer-checked:bg-orange-600 transition" style="border-radius: 12px;"></span>
        <span class="absolute left-1 top-1 w-4 h-4 bg-white transition-transform peer-checked:translate-x-4" style="border-radius: 50%;"></span>
    </span>
</label>

<div class="pt-1">
    <p class="text-xs font-mono text-zinc-400 mb-1.5">TAK ZOBACZY TO KLIENT</p>
    <div class="flex items-center justify-between gap-4 px-4 py-2.5 bg-[#15161A] border border-zinc-800" style="border-radius: 2px;">
        <div class="min-w-0">
            <p class="text-sm font-medium text-zinc-100">
                <span id="podglad-nazwa">Nazwa usługi</span>
                <span id="podglad-czas" class="ml-2 text-xs font-mono text-zinc-500 font-normal"></span>
            </p>
            <p id="podglad-opis" class="text-xs text-zinc-500 truncate"></p>
        </div>
        <p id="podglad-cena" class="text-sm font-semibold text-zinc-100 whitespace-nowrap"></p>
    </div>
</div>

<script>
(function () {
    const KROK = 30, MIN = 30, MAKS = 480;
    const mechanicy = @json($mechanicyWgSpec);

    const poleCzas = document.getElementById('pole-czas');
    const opisCzasu = document.getElementById('czas-opis');
    const minus = document.getElementById('czas-minus');
    const plus = document.getElementById('czas-plus');
    const nazwa = document.getElementById('pole-nazwa');
    const opis = document.getElementById('pole-opis');
    const cena = document.getElementById('pole-cena');
    const spec = document.getElementById('pole-spec');
    const podpowiedz = document.getElementById('spec-podpowiedz');

    function formatCzas(min) {
        const h = Math.floor(min / 60), m = min % 60;
        return ((h ? h + ' h ' : '') + (m ? m + ' min' : '')).trim();
    }

    function odswiezCzas() {
        const v = parseInt(poleCzas.value, 10);
        opisCzasu.textContent = formatCzas(v);
        minus.disabled = v <= MIN;
        plus.disabled = v >= MAKS;
        podglad();
    }

    function zmienCzas(delta) {
        const v = Math.min(MAKS, Math.max(MIN, parseInt(poleCzas.value, 10) + delta));
        poleCzas.value = v;
        odswiezCzas();
    }

    function odswiezSpec() {
        const lista = mechanicy[spec.value];
        if (!spec.value) {
            podpowiedz.className = 'text-xs mt-1.5 text-zinc-500';
            podpowiedz.textContent = 'Usługę mogą wykonać tylko mechanicy z tą specjalizacją.';
        } else if (!lista || lista.length === 0) {
            podpowiedz.className = 'text-xs mt-1.5 text-red-400';
            podpowiedz.textContent = 'Nikt nie ma tej specjalizacji – usługa nie będzie miała wolnych terminów, dopóki nie przypiszesz mechanika.';
        } else {
            podpowiedz.className = 'text-xs mt-1.5 text-zinc-400';
            podpowiedz.textContent = 'Wykonają: ' + lista.join(', ');
        }
    }

    function podglad() {
        document.getElementById('podglad-nazwa').textContent = nazwa.value.trim() || 'Nazwa usługi';
        document.getElementById('podglad-czas').textContent = formatCzas(parseInt(poleCzas.value, 10));
        document.getElementById('podglad-opis').textContent = opis.value.trim();
        const c = parseFloat(cena.value);
        document.getElementById('podglad-cena').textContent = isNaN(c) ? '— zł' : c.toFixed(2) + ' zł';
    }

    minus.addEventListener('click', () => zmienCzas(-KROK));
    plus.addEventListener('click', () => zmienCzas(KROK));
    spec.addEventListener('change', odswiezSpec);
    [nazwa, opis, cena].forEach(el => el.addEventListener('input', podglad));

    odswiezCzas();
    odswiezSpec();
})();
</script>