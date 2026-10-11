@php $st = $stanowisko ?? null; @endphp

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">NAZWA STANOWISKA</label>
    <input type="text" name="nazwa" value="{{ old('nazwa', $st->nazwa ?? '') }}" placeholder="np. Podnośnik 3"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    @error('nazwa') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">TYP</label>
    <select name="typ"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @foreach (\App\Models\Stanowisko::TYPY as $wartosc => $etykieta)
            <option value="{{ $wartosc }}" {{ old('typ', $st->typ ?? 'podnosnik') === $wartosc ? 'selected' : '' }}>
                {{ $etykieta }}
            </option>
        @endforeach
    </select>
    @error('typ') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<label class="flex items-center gap-2 text-sm text-zinc-300">
    <input type="checkbox" name="czy_aktywne" value="1" {{ old('czy_aktywne', $st->czy_aktywne ?? true) ? 'checked' : '' }}
        class="w-4 h-4 accent-orange-600">
    Stanowisko aktywne (dostępne przy nowych rezerwacjach)
</label>