@php $p = $pojazd ?? null; @endphp

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">MARKA</label>
        <input type="text" name="marka" value="{{ old('marka', $p->marka ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('marka') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">MODEL</label>
        <input type="text" name="model" value="{{ old('model', $p->model ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('model') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">ROK PRODUKCJI (OPCJONALNIE)</label>
        <input type="number" name="rok_produkcji" value="{{ old('rok_produkcji', $p->rok_produkcji ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('rok_produkcji') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">NR REJESTRACYJNY</label>
        <input type="text" name="numer_rejestracyjny" value="{{ old('numer_rejestracyjny', $p->numer_rejestracyjny ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('numer_rejestracyjny') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">VIN (OPCJONALNIE)</label>
    <input type="text" name="vin" maxlength="17" value="{{ old('vin', $p->vin ?? '') }}"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    @error('vin') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>