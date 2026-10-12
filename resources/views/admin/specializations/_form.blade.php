@php
    $sp = $specjalizacja ?? null;
    $przypisani = old('pracownicy', $sp ? $sp->pracownicy->pluck('id_uzytkownika')->all() : []);
@endphp

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">NAZWA SPECJALIZACJI</label>
    <input type="text" name="nazwa" value="{{ old('nazwa', $sp->nazwa ?? '') }}" placeholder="np. Geometria kół"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    @error('nazwa') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-2">MECHANICY Z TĄ SPECJALIZACJĄ</label>
    @forelse ($pracownicy as $p)
        <label class="flex items-center gap-2 text-sm text-zinc-300 py-1">
            <input type="checkbox" name="pracownicy[]" value="{{ $p->id_uzytkownika }}"
                   {{ in_array($p->id_uzytkownika, $przypisani) ? 'checked' : '' }}
                   class="w-4 h-4 accent-orange-600">
            {{ $p->imie }} {{ $p->nazwisko }}
        </label>
    @empty
        <p class="text-sm text-zinc-500">Brak użytkowników z rolą pracownika.</p>
    @endforelse
    @if ($errors->has('pracownicy.*')) <p class="text-sm text-red-500 mt-1">{{ $errors->first('pracownicy.*') }}</p> @endif
</div>