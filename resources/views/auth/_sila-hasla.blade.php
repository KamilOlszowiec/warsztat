{{-- Użycie: @include('auth._sila-hasla', ['pole' => 'id-pola-hasla', 'powtorzenie' => 'id-pola-powtorzenia']) --}}
<div class="flex items-center gap-2.5" aria-live="polite">
    <span class="flex-1 grid grid-cols-4 gap-1">
        @for ($i = 0; $i < 4; $i++)
            <span class="sila-pasek h-1 bg-zinc-800" style="border-radius: 2px;"></span>
        @endfor
    </span>
    <span id="sila-opis" class="text-xs text-zinc-500 whitespace-nowrap">minimum 8 znaków</span>
</div>
<p id="zgodnosc-opis" class="hidden text-xs mt-1.5"></p>

<script>
(function () {
    const MIN = 8;
    const pole = document.getElementById(@json($pole));
    const powtorzenie = document.getElementById(@json($powtorzenie));
    const paski = document.querySelectorAll('.sila-pasek');
    const opis = document.getElementById('sila-opis');
    const zgodnosc = document.getElementById('zgodnosc-opis');

    function ocen(h) {
        let punkty = 0;
        if (h.length >= MIN) punkty++;
        if (h.length >= 12) punkty++;
        if (/[a-ząćęłńóśźż]/.test(h) && /[A-ZĄĆĘŁŃÓŚŹŻ]/.test(h)) punkty++;
        if (/\d/.test(h) && /[^A-Za-z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/.test(h)) punkty++;
        else if (/\d/.test(h) || /[^A-Za-z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/.test(h)) punkty += 0.5;
        return Math.min(4, Math.round(punkty));
    }

    function odswiez() {
        const h = pole.value;
        let kolor = 'bg-zinc-800', tekst = 'minimum 8 znaków', klasaTekstu = 'text-zinc-500', ile = 0;

        if (h.length > 0 && h.length < MIN) {
            const brak = MIN - h.length;
            ile = 1; kolor = 'bg-yellow-400'; klasaTekstu = 'text-yellow-400';
            tekst = 'jeszcze ' + brak + ' ' + (brak === 1 ? 'znak' : (brak < 5 ? 'znaki' : 'znaków')) + ' – minimum to 8';
        } else if (h.length >= MIN) {
            ile = Math.max(1, ocen(h));
            if (ile <= 1)      { kolor = 'bg-yellow-400'; klasaTekstu = 'text-yellow-400'; tekst = 'słabe – dodaj cyfrę lub wielką literę'; }
            else if (ile === 2) { kolor = 'bg-orange-500'; klasaTekstu = 'text-orange-400'; tekst = 'średnie'; }
            else               { kolor = 'bg-green-400';  klasaTekstu = 'text-green-400';  tekst = 'mocne'; }
        }

        paski.forEach((p, i) => p.className = 'sila-pasek h-1 ' + (i < ile ? kolor : 'bg-zinc-800'));
        opis.className = 'text-xs whitespace-nowrap ' + klasaTekstu;
        opis.textContent = tekst;

        if (powtorzenie.value.length > 0) {
            const zgodne = powtorzenie.value === h;
            zgodnosc.classList.remove('hidden');
            zgodnosc.className = 'text-xs mt-1.5 ' + (zgodne ? 'text-green-400' : 'text-red-400');
            zgodnosc.textContent = zgodne ? 'Hasła są takie same.' : 'Hasła się różnią.';
        } else {
            zgodnosc.classList.add('hidden');
        }
    }

    pole.addEventListener('input', odswiez);
    powtorzenie.addEventListener('input', odswiez);
})();
</script>