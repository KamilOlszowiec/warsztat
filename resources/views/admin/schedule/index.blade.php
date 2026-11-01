@extends('layouts.admin')
@section('title', 'Godziny pracy')

@section('content')
@php
    // Godziny do wyboru: co 30 minut, 05:00–22:00
    $opcje = [];
    for ($m = 5 * 60; $m <= 22 * 60; $m += 30) {
        $opcje[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }
    $pole = 'h-9 px-2 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm font-mono focus:outline-none focus:border-orange-600';
@endphp

<div class="max-w-4xl space-y-5">

    <form id="form-godzin" method="POST" action="{{ route('admin.schedule.update') }}">
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
            <div>
                <p class="text-sm font-mono text-orange-500 mb-1">USTAWIENIA</p>
                <h1 class="text-2xl font-bold text-zinc-50">Godziny pracy warsztatu</h1>
                <p class="text-sm text-zinc-400 mt-1.5">Otwarte łącznie <strong id="suma-godzin" class="text-zinc-100">—</strong> w tygodniu</p>
            </div>
            <button type="submit" class="min-h-[44px] px-6 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
                Zapisz godziny
            </button>
        </div>

        @if ($errors->any())
            <div class="mb-5 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;" aria-label="Godziny w dniach tygodnia">
            {{-- Podziałka osi czasu --}}
            <div class="hidden md:grid grid-cols-[170px_220px_1fr] items-center px-5 py-2.5 border-b border-zinc-800">
                <span></span><span></span>
                <div class="relative h-3.5 text-[10px] font-mono text-zinc-500">
                    @for ($t = 6 * 60; $t <= 20 * 60; $t += 120)
                        <span class="absolute top-0 -translate-x-1/2" style="left: {{ ($t - 360) / 840 * 100 }}%;">{{ sprintf('%02d:00', intdiv($t, 60)) }}</span>
                    @endfor
                </div>
            </div>

            @foreach ($godziny as $i => $g)
                @php
                    $czynne = (bool) old("dni.$i.czy_czynne", $g->czy_czynne);
                    $od = old("dni.$i.godzina_otwarcia", $g->godzina_otwarcia ? substr($g->godzina_otwarcia, 0, 5) : '08:00');
                    $do = old("dni.$i.godzina_zamkniecia", $g->godzina_zamkniecia ? substr($g->godzina_zamkniecia, 0, 5) : '16:00');
                @endphp
                <div class="wiersz-dnia grid grid-cols-1 md:grid-cols-[170px_220px_1fr] items-center gap-3 md:gap-0 px-5 py-3 border-b border-zinc-800 last:border-b-0">
                    <input type="hidden" name="dni[{{ $i }}][id_dnia]" value="{{ $g->id_dnia }}">

                    {{-- Przełącznik --}}
                    <label class="flex items-center gap-3 cursor-pointer">
                        <span class="relative shrink-0">
                            <input type="checkbox" name="dni[{{ $i }}][czy_czynne]" value="1" @checked($czynne) class="pole-czynne peer sr-only">
                            <span class="block w-10 h-6 bg-zinc-700 peer-checked:bg-orange-600 transition" style="border-radius: 12px;"></span>
                            <span class="absolute left-1 top-1 w-4 h-4 bg-white transition-transform peer-checked:translate-x-4" style="border-radius: 50%;"></span>
                        </span>
                        <span class="text-sm font-medium text-zinc-100">{{ $nazwyDni[$g->dzien_tygodnia] ?? $g->dzien_tygodnia }}</span>
                    </label>

                    {{-- Godziny --}}
                    <div class="godziny flex items-center gap-2">
                        <label class="sr-only" for="od-{{ $i }}">Otwarcie</label>
                        <select id="od-{{ $i }}" name="dni[{{ $i }}][godzina_otwarcia]" class="pole-od {{ $pole }}" style="border-radius: 2px;">
                            @foreach ($opcje as $o)
                                <option value="{{ $o }}" @selected($od === $o)>{{ $o }}</option>
                            @endforeach
                        </select>
                        <span class="text-zinc-500">–</span>
                        <label class="sr-only" for="do-{{ $i }}">Zamknięcie</label>
                        <select id="do-{{ $i }}" name="dni[{{ $i }}][godzina_zamkniecia]" class="pole-do {{ $pole }}" style="border-radius: 2px;">
                            @foreach ($opcje as $o)
                                <option value="{{ $o }}" @selected($do === $o)>{{ $o }}</option>
                            @endforeach
                        </select>
                        <span class="napis-zamkniete hidden text-sm text-zinc-500">zamknięte</span>
                    </div>

                    {{-- Pasek na osi 6:00–20:00 --}}
                    <div class="hidden md:block relative h-[22px]" style="background: repeating-linear-gradient(to right, #27272A 0 1px, transparent 1px 7.142857%); border-radius: 2px;">
                        <span class="pasek absolute top-[3px] bottom-[3px] bg-orange-600/50 border border-orange-600" style="border-radius: 2px;"></span>
                    </div>
                </div>
            @endforeach
        </section>
    </form>

    <div class="flex gap-3 items-start px-4 py-3.5 bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 shrink-0 mt-0.5">
            <circle cx="12" cy="12" r="9"></circle><path d="M12 8v5M12 16h.01"></path>
        </svg>
        <p class="text-[13px] leading-relaxed text-zinc-300">
            Godziny dotyczą nowych rezerwacji i przycisku „Wypełnij wg godzin pracy” w grafiku. Wizyty już umówione nie zostaną przesunięte –
            jeśli któraś wypadnie poza nowymi godzinami, zobaczysz ostrzeżenie po zapisie. Pojedyncze dni (święta, inwentaryzacja) ustawisz w
            <a href="{{ route('admin.days-off.index') }}" class="text-orange-500 hover:text-orange-400">Dniach wolnych</a>.
        </p>
    </div>
</div>

<script>
(function () {
    const OD = 360, DO = 1200; // oś 06:00–20:00
    const wiersze = Array.from(document.querySelectorAll('.wiersz-dnia'));
    const sumaEl = document.getElementById('suma-godzin');

    const naMinuty = (t) => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    const proc = (m) => Math.min(100, Math.max(0, (m - OD) / (DO - OD) * 100));

    function odswiez() {
        let suma = 0;

        wiersze.forEach(w => {
            const czynne = w.querySelector('.pole-czynne').checked;
            const od = naMinuty(w.querySelector('.pole-od').value);
            const doM = naMinuty(w.querySelector('.pole-do').value);
            const pasek = w.querySelector('.pasek');
            const poprawne = doM > od;

            // Przy zamkniętym dniu pola zostają (wrócą po włączeniu), ale są schowane
            w.querySelectorAll('select').forEach(s => s.classList.toggle('hidden', !czynne));
            w.querySelector('.napis-zamkniete').classList.toggle('hidden', czynne);
            w.querySelector('.pole-do').classList.toggle('border-red-500', czynne && !poprawne);

            if (czynne && poprawne) {
                suma += doM - od;
                pasek.style.display = '';
                pasek.style.left = proc(od) + '%';
                pasek.style.width = (proc(doM) - proc(od)) + '%';
            } else {
                pasek.style.display = 'none';
            }
        });

        const h = suma / 60;
        sumaEl.textContent = (Number.isInteger(h) ? h : h.toFixed(1).replace('.', ',')) + ' h';
    }

    document.getElementById('form-godzin').addEventListener('change', odswiez);
    odswiez();
})();
</script>
@endsection