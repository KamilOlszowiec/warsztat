@php $s = $usluga ?? null; @endphp

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">NAZWA USŁUGI</label>
    <input type="text" name="nazwa" value="{{ old('nazwa', $s->nazwa ?? '') }}"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    @error('nazwa') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">OPIS (OPCJONALNIE)</label>
    <textarea name="opis" rows="3"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">{{ old('opis', $s->opis ?? '') }}</textarea>
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">CENA (ZŁ)</label>
        <input type="number" step="0.01" min="0" name="cena" value="{{ old('cena', $s->cena ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('cena') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">CZAS TRWANIA (MIN)</label>
        <input type="number" min="30" step="30" name="czas_trwania" value="{{ old('czas_trwania', $s->czas_trwania ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        <p class="text-xs text-zinc-500 mt-1">Wielokrotność 30 minut.</p>
        @error('czas_trwania') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">MAKS. JEDNOCZEŚNIE</label>
    <input type="number" min="1" max="20" name="maks_jednoczesnie" value="{{ old('maks_jednoczesnie', $s->maks_jednoczesnie ?? 1) }}"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    <p class="text-xs text-zinc-500 mt-1">Ile razy ta usługa może być realizowana w tym samym czasie (np. ograniczenie sprzętowe).</p>
    @error('maks_jednoczesnie') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<label class="flex items-center gap-2 text-sm text-zinc-300">
    <input type="checkbox" name="czy_aktywna" value="1" {{ old('czy_aktywna', $s->czy_aktywna ?? true) ? 'checked' : '' }}
        class="w-4 h-4 accent-orange-600">
    Usługa aktywna (widoczna dla klientów)
</label>