@php
    $u = $user ?? null;
    $wybrane = old('specjalizacje', $u ? $u->specjalizacje->pluck('id_specjalizacji')->all() : []);
    $rola = old('rola', $u->rola ?? 'pracownik');
    $pole = 'w-full h-[42px] px-3 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm focus:outline-none focus:border-orange-600';
@endphp

@if ($errors->any() && !$errors->has('error'))
    <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
        Popraw zaznaczone pola: {{ implode(' ', $errors->all()) }}
    </div>
@endif

<section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
    <h2 class="px-5 py-4 border-b border-zinc-800 text-[15px] font-semibold text-zinc-50">Dane konta</h2>
    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <label class="block">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">IMIĘ</span>
            <input type="text" name="imie" value="{{ old('imie', $u->imie ?? '') }}" required class="{{ $pole }}" style="border-radius: 2px;">
        </label>
        <label class="block">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">NAZWISKO</span>
            <input type="text" name="nazwisko" value="{{ old('nazwisko', $u->nazwisko ?? '') }}" required class="{{ $pole }}" style="border-radius: 2px;">
        </label>
        <label class="block">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">E-MAIL (LOGIN)</span>
            <input type="email" name="email" value="{{ old('email', $u->email ?? '') }}" required class="{{ $pole }}" style="border-radius: 2px;">
        </label>
        <label class="block">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">TELEFON</span>
            <input type="text" name="telefon" value="{{ old('telefon', $u->telefon ?? '') }}" class="{{ $pole }}" style="border-radius: 2px;">
        </label>
        <label class="block">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">ROLA</span>
            <select name="rola" id="pole-rola" class="{{ $pole }}" style="border-radius: 2px;">
                <option value="pracownik" @selected($rola === 'pracownik')>Mechanik (pracownik)</option>
                <option value="administrator" @selected($rola === 'administrator')>Administrator</option>
            </select>
        </label>
    </div>
</section>

@unless ($u)
    <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
        <h2 class="px-5 py-4 border-b border-zinc-800 text-[15px] font-semibold text-zinc-50">Hasło startowe</h2>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="block">
                <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">HASŁO (MIN. 8 ZNAKÓW)</span>
                <input type="password" name="password" required autocomplete="new-password" class="{{ $pole }}" style="border-radius: 2px;">
            </label>
            <label class="block">
                <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">POWTÓRZ HASŁO</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $pole }}" style="border-radius: 2px;">
            </label>
            <p class="sm:col-span-2 text-xs text-zinc-400">Przekaż hasło pracownikowi i poproś, żeby zmienił je w profilu po pierwszym logowaniu.</p>
        </div>
    </section>
@endunless

<section id="sekcja-specjalizacji" class="bg-[#1F2128] border border-zinc-800 {{ $rola === 'pracownik' ? '' : 'hidden' }}" style="border-radius: 2px;">
    <div class="flex items-baseline justify-between gap-3 px-5 py-4 border-b border-zinc-800">
        <h2 class="text-[15px] font-semibold text-zinc-50">Specjalizacje</h2>
        <a href="{{ route('admin.specializations.index') }}" class="text-[13px] text-orange-500 hover:text-orange-400">Zarządzaj listą →</a>
    </div>
    <div class="p-5 grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-2">
        @forelse ($specjalizacje as $s)
            <label class="flex items-center gap-2.5 min-h-[42px] px-3 border border-zinc-700 has-[:checked]:border-orange-600 has-[:checked]:bg-orange-600/10 cursor-pointer transition" style="border-radius: 2px;">
                <input type="checkbox" name="specjalizacje[]" value="{{ $s->id_specjalizacji }}" @checked(in_array($s->id_specjalizacji, $wybrane))
                       class="pole-spec w-4 h-4 accent-orange-600">
                <span class="text-sm text-zinc-100">{{ $s->nazwa }}</span>
            </label>
        @empty
            <p class="text-sm text-zinc-400">Nie ma jeszcze żadnych specjalizacji.</p>
        @endforelse
    </div>
    <div class="px-5 pt-3.5 pb-5 border-t border-zinc-800">
        <p class="text-[13px] text-zinc-300 mb-2.5">Może wykonywać <strong id="licznik-uslug" class="text-zinc-50">0</strong> z {{ $uslugi->count() }} usług:</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($uslugi as $usl)
                <span class="usluga-podglad text-xs px-2 py-0.5" data-spec="{{ $usl->id_specjalizacji }}" style="border-radius: 2px;">{{ $usl->nazwa }}</span>
            @endforeach
        </div>
        <p id="ostrzezenie-spec" class="hidden mt-3 text-[13px] text-red-400">Bez specjalizacji system nie przydzieli temu pracownikowi żadnej wizyty.</p>
    </div>
</section>

<script>
(function () {
    const rola = document.getElementById('pole-rola');
    const sekcja = document.getElementById('sekcja-specjalizacji');
    const pola = Array.from(document.querySelectorAll('.pole-spec'));
    const uslugi = Array.from(document.querySelectorAll('.usluga-podglad'));
    const licznik = document.getElementById('licznik-uslug');
    const ostrzezenie = document.getElementById('ostrzezenie-spec');

    function odswiez() {
        const wybrane = pola.filter(p => p.checked).map(p => p.value);
        let dostepne = 0;

        uslugi.forEach(u => {
            // Usługa bez wymaganej specjalizacji jest dostępna dla każdego mechanika
            const ok = !u.dataset.spec || wybrane.includes(u.dataset.spec);
            if (ok) dostepne++;
            u.className = 'usluga-podglad text-xs px-2 py-0.5 ' + (ok
                ? 'bg-zinc-800 text-zinc-100 border border-zinc-600'
                : 'text-zinc-500 border border-dashed border-zinc-700 line-through');
        });

        licznik.textContent = dostepne;
        ostrzezenie.classList.toggle('hidden', wybrane.length > 0);
    }

    rola.addEventListener('change', () => sekcja.classList.toggle('hidden', rola.value !== 'pracownik'));
    pola.forEach(p => p.addEventListener('change', odswiez));
    odswiez();
})();
</script>