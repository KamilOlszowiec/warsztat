{{-- resources/views/admin/users/_form.blade.php --}}
@php $u = $user ?? null; @endphp

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">IMIĘ</label>
        <input type="text" name="imie" value="{{ old('imie', $u->imie ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('imie') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-xs font-mono text-zinc-400 mb-1.5">NAZWISKO</label>
        <input type="text" name="nazwisko" value="{{ old('nazwisko', $u->nazwisko ?? '') }}"
            class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('nazwisko') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">EMAIL</label>
    <input type="email" name="email" value="{{ old('email', $u->email ?? '') }}"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    @error('email') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">TELEFON (OPCJONALNIE)</label>
    <input type="text" name="telefon" value="{{ old('telefon', $u->telefon ?? '') }}"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">ROLA</label>
    <select name="rola" class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @foreach (['klient', 'pracownik', 'administrator'] as $rola)
            <option value="{{ $rola }}" {{ old('rola', $u->rola ?? '') === $rola ? 'selected' : '' }}>
                {{ strtoupper($rola) }}
            </option>
        @endforeach
    </select>
    @error('rola') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-xs font-mono text-zinc-400 mb-1.5">
        HASŁO {{ $u ? '(zostaw puste, aby nie zmieniać)' : '' }}
    </label>
    <input type="password" name="password"
        class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
    @error('password') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
</div>