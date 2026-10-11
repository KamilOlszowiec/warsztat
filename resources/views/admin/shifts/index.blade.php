@extends('layouts.admin')
@section('title', 'Grafik mechaników')

@section('content')
@php $skroty = ['PN', 'WT', 'ŚR', 'CZ', 'PT', 'SO', 'ND']; @endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">GRAFIK</p>
        <h1 class="text-2xl font-bold text-zinc-50">Grafik mechaników</h1>
    </div>
    <div class="flex gap-2">
        <form method="POST" action="{{ route('admin.shifts.copy') }}"
              onsubmit="return confirm('Zastąpić grafik tego tygodnia kopią poprzedniego tygodnia?');">
            @csrf
            <input type="hidden" name="tydzien" value="{{ $poczatek->toDateString() }}">
            <button type="submit" class="border border-zinc-600 hover:border-zinc-400 text-zinc-300 text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
                Kopiuj poprzedni tydzień
            </button>
        </form>
        <form method="POST" action="{{ route('admin.shifts.fill') }}"
              onsubmit="return confirm('Ustawić wszystkim mechanikom zmiany zgodne z godzinami pracy warsztatu? Obecny grafik tego tygodnia zostanie nadpisany.');">
            @csrf
            <input type="hidden" name="tydzien" value="{{ $poczatek->toDateString() }}">
            <button type="submit" class="border border-orange-600 text-orange-500 hover:bg-orange-600 hover:text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
                Wypełnij wg godzin pracy
            </button>
        </form>
    </div>
</div>

{{-- Nawigacja tygodni --}}
<div class="flex items-center gap-4 mb-4 text-sm">
    <a href="{{ route('admin.shifts.index', ['tydzien' => $poczatek->copy()->subWeek()->toDateString()]) }}"
       class="text-orange-500 hover:text-orange-400">← Poprzedni</a>
    <span class="font-mono text-zinc-200">{{ $poczatek->format('d.m') }} – {{ $koniec->format('d.m.Y') }}</span>
    <a href="{{ route('admin.shifts.index', ['tydzien' => $poczatek->copy()->addWeek()->toDateString()]) }}"
       class="text-orange-500 hover:text-orange-400">Następny →</a>
    <a href="{{ route('admin.shifts.index') }}" class="text-zinc-400 hover:text-zinc-200">Bieżący tydzień</a>
</div>

@error('grafik')
    <p class="text-sm text-red-500 mb-4">{{ $message }}</p>
@enderror

@if ($pracownicy->isEmpty())
    <div class="bg-[#1F2128] border border-zinc-800 p-6 text-zinc-500" style="border-radius: 2px;">
        Brak użytkowników z rolą pracownika. Nadaj rolę w zakładce Użytkownicy.
    </div>
@else
<form method="POST" action="{{ route('admin.shifts.save') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="tydzien" value="{{ $poczatek->toDateString() }}">

    <div class="bg-[#1F2128] border border-zinc-800 overflow-x-auto" style="border-radius: 2px;">
        <table id="tabela-grafiku" class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left">
                    <th class="px-4 py-3 font-mono text-xs text-zinc-500">MECHANIK</th>
                    @foreach ($dni as $dzien)
                        @php $godziny = $godzinyPracy->get($dzien->dayOfWeekIso); @endphp
                        <th class="px-2 py-3 font-mono text-xs {{ $dzien->isToday() ? 'text-orange-500' : 'text-zinc-500' }}">
                            {{ $skroty[$dzien->dayOfWeekIso - 1] }} {{ $dzien->format('d.m') }}
                            @if ($godziny && $godziny->czy_czynne)
                                <span class="block text-[10px] text-zinc-600 font-normal">
                                    {{ substr($godziny->godzina_otwarcia, 0, 5) }}–{{ substr($godziny->godzina_zamkniecia, 0, 5) }}
                                </span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($pracownicy as $p)
                    <tr class="border-b border-zinc-800/50">
                        <td class="px-4 py-3 text-zinc-200 whitespace-nowrap">{{ $p->imie }} {{ $p->nazwisko }}</td>

                        @foreach ($dni as $dzien)
                            @php
                                $data = $dzien->toDateString();
                                $godziny = $godzinyPracy->get($dzien->dayOfWeekIso);
                                $zmiana = $zmiany->get($p->id_uzytkownika . '_' . $data);
                                $nazwa = "zmiany[{$p->id_uzytkownika}][{$data}]";
                                $klucz = "zmiany.{$p->id_uzytkownika}.{$data}";
                            @endphp
                            <td class="px-2 py-2 align-top min-w-[112px]">
                                @if ($dniWolne->has($data))
                                    <p class="text-xs text-zinc-500">Dzień wolny</p>
                                    <p class="text-[10px] text-zinc-600">{{ $dniWolne->get($data)->opis }}</p>
                                @elseif (!$godziny || !$godziny->czy_czynne)
                                    <p class="text-xs text-zinc-600">Nieczynne</p>
                                @else
                                    @php
                                        $otwarcie = substr($godziny->godzina_otwarcia, 0, 5);
                                        $zamkniecie = substr($godziny->godzina_zamkniecia, 0, 5);

                                        // Godziny co 30 minut od otwarcia do zamknięcia
                                        $siatka = [];
                                        $t = \Carbon\Carbon::parse($data . ' ' . $otwarcie);
                                        $koniecDnia = \Carbon\Carbon::parse($data . ' ' . $zamkniecie);
                                        while ($t->lte($koniecDnia)) {
                                            $siatka[] = $t->format('H:i');
                                            $t->addMinutes(30);
                                        }
                                        $opcjeOd = array_slice($siatka, 0, -1);
                                        $opcjeDo = array_slice($siatka, 1);

                                        $wartoscOd = old($klucz . '.od', $zmiana ? substr($zmiana->godzina_od, 0, 5) : '');
                                        $wartoscDo = old($klucz . '.do', $zmiana ? substr($zmiana->godzina_do, 0, 5) : '');

                                        // Zachowanie wartości spoza siatki (np. po zmianie godzin pracy)
                                        if ($wartoscOd && !in_array($wartoscOd, $opcjeOd, true)) array_unshift($opcjeOd, $wartoscOd);
                                        if ($wartoscDo && !in_array($wartoscDo, $opcjeDo, true)) $opcjeDo[] = $wartoscDo;

                                        $klasaSelect = 'w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-2 py-1.5 text-xs focus:outline-none focus:border-orange-600';
                                    @endphp
                                    <div class="komorka-zmiany space-y-1" data-otwarcie="{{ $otwarcie }}" data-zamkniecie="{{ $zamkniecie }}">
                                        <select name="{{ $nazwa }}[od]" class="pole-od {{ $klasaSelect }}" style="border-radius: 2px;">
                                            <option value="">od —</option>
                                            @foreach ($opcjeOd as $h)
                                                <option value="{{ $h }}" {{ $wartoscOd === $h ? 'selected' : '' }}>{{ $h }}</option>
                                            @endforeach
                                        </select>
                                        <select name="{{ $nazwa }}[do]" class="pole-do {{ $klasaSelect }}" style="border-radius: 2px;">
                                            <option value="">do —</option>
                                            @foreach ($opcjeDo as $h)
                                                <option value="{{ $h }}" {{ $wartoscDo === $h ? 'selected' : '' }}>{{ $h }}</option>
                                            @endforeach
                                        </select>
                                        <div class="flex gap-1 pt-0.5">
                                            <button type="button" data-akcja="caly"
                                                class="flex-1 text-[10px] font-mono border border-zinc-700 text-zinc-400 hover:border-orange-600 hover:text-orange-500 py-0.5 transition" style="border-radius: 2px;">
                                                CAŁY DZIEŃ
                                            </button>
                                            <button type="button" data-akcja="wolne"
                                                class="flex-1 text-[10px] font-mono border border-zinc-700 text-zinc-400 hover:border-red-500 hover:text-red-400 py-0.5 transition" style="border-radius: 2px;">
                                                WOLNE
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-4">
        <p class="text-xs text-zinc-500">Przygaszona komórka oznacza dzień wolny lub urlop mechanika. Zmiany zapisują się dopiero po kliknięciu „Zapisz grafik”.</p>
        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-6 py-2.5 transition" style="border-radius: 2px;">
            Zapisz grafik
        </button>
    </div>
</form>

<script>
(function () {
    const tabela = document.getElementById('tabela-grafiku');

    // Przygaszenie komórki, gdy mechanik nie ma zmiany
    function odswiezWyglad(komorka) {
        const od = komorka.querySelector('.pole-od').value;
        const doGodz = komorka.querySelector('.pole-do').value;
        const wolne = !od && !doGodz;
        komorka.querySelectorAll('select').forEach(s => s.classList.toggle('opacity-40', wolne));
    }

    tabela.addEventListener('click', function (e) {
        const przycisk = e.target.closest('button[data-akcja]');
        if (!przycisk) return;

        const komorka = przycisk.closest('.komorka-zmiany');
        const poleOd = komorka.querySelector('.pole-od');
        const poleDo = komorka.querySelector('.pole-do');

        if (przycisk.dataset.akcja === 'wolne') {
            poleOd.value = '';
            poleDo.value = '';
        } else {
            poleOd.value = komorka.dataset.otwarcie;
            poleDo.value = komorka.dataset.zamkniecie;
        }

        odswiezWyglad(komorka);
    });

    tabela.addEventListener('change', function (e) {
        const komorka = e.target.closest('.komorka-zmiany');
        if (!komorka) return;

        const poleOd = komorka.querySelector('.pole-od');
        const poleDo = komorka.querySelector('.pole-do');

        // Uzupełnienie drugiego pola, żeby nie zostawić połowy zmiany
        if (e.target === poleOd && poleOd.value && !poleDo.value) {
            poleDo.value = komorka.dataset.zamkniecie;
        }
        if (e.target === poleDo && poleDo.value && !poleOd.value) {
            poleOd.value = komorka.dataset.otwarcie;
        }

        odswiezWyglad(komorka);
    });

    tabela.querySelectorAll('.komorka-zmiany').forEach(odswiezWyglad);
})();
</script>
@endif
@endsection