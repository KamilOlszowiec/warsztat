@extends('layouts.admin')
@section('title', 'Dni wolne')

@section('content')
@php
    $skrotyDni = ['PN', 'WT', 'ŚR', 'CZW', 'PT', 'SO', 'ND'];
    $odmianaWizyt = fn ($n) => $n === 1 ? 'umówiona wizyta' : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14) ? 'umówione wizyty' : 'umówionych wizyt');
@endphp

<div class="max-w-4xl space-y-5">

    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">USTAWIENIA</p>
        <h1 class="text-2xl font-bold text-zinc-50">Dni wolne warsztatu</h1>
        <p class="text-sm text-zinc-400 mt-1.5">W te dni warsztat jest zamknięty – klienci nie zobaczą wolnych terminów, niezależnie od grafiku.</p>
    </div>

    <form id="form-dnia-wolnego" method="POST" action="{{ route('admin.days-off.store') }}"
          class="bg-[#1F2128] border border-zinc-800 p-5 flex flex-wrap items-end gap-3" style="border-radius: 2px;">
        @csrf
        <label class="w-[180px]">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">DATA</span>
            <input type="date" id="pole-data" name="data" min="{{ now()->format('Y-m-d') }}" value="{{ old('data') }}" required
                class="w-full h-[42px] px-2.5 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm focus:outline-none focus:border-orange-600 [color-scheme:dark]" style="border-radius: 2px;">
        </label>
        <label class="flex-1 min-w-[220px]">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">OPIS (OPCJONALNIE)</span>
            <input type="text" name="opis" value="{{ old('opis') }}" maxlength="100" placeholder="np. Wigilia, inwentaryzacja, szkolenie zespołu"
                class="w-full h-[42px] px-3 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm placeholder-zinc-500 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        </label>
        <button type="submit" class="h-[42px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            + Dodaj dzień wolny
        </button>
        <p id="ostrzezenie-wizyt" class="hidden w-full text-[13px] text-red-300"></p>
        @error('data') <p class="w-full text-sm text-red-400">{{ $message }}</p> @enderror
        @error('opis') <p class="w-full text-sm text-red-400">{{ $message }}</p> @enderror
    </form>

    <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;" aria-label="Zaplanowane dni wolne">
        <div class="flex items-baseline justify-between px-5 py-4 border-b border-zinc-800">
            <h2 class="text-[15px] font-semibold text-zinc-50">Zaplanowane</h2>
            <span class="text-[11px] font-mono text-zinc-400">{{ $dniWolne->count() }} {{ $dniWolne->count() === 1 ? 'DZIEŃ' : 'DNI' }}</span>
        </div>

        @forelse ($dniWolne as $d)
            @php
                $za = (int) today()->diffInDays($d->data);
                $kiedy = $za === 0 ? 'dziś' : ($za === 1 ? 'jutro' : "za {$za} dni");
            @endphp
            <div class="flex items-center gap-4 px-5 py-4 {{ $loop->last ? '' : 'border-b border-zinc-800' }}">
                <div class="w-[58px] shrink-0 text-center py-2 bg-[#18191D] border border-zinc-800" style="border-radius: 2px;">
                    <p class="text-[10px] font-mono text-zinc-400">{{ $skrotyDni[$d->data->dayOfWeekIso - 1] }}</p>
                    <p class="text-[22px] font-bold text-zinc-50 leading-tight">{{ $d->data->format('d') }}</p>
                    <p class="text-[10px] font-mono text-zinc-400">{{ mb_strtoupper($d->data->translatedFormat('M'), 'UTF-8') }}</p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-zinc-50">{{ $d->opis ?: 'Dzień wolny' }}</p>
                    @if ($d->liczba_rezerwacji > 0)
                        <p class="text-xs text-red-300 mt-0.5">{{ $d->liczba_rezerwacji }} {{ $odmianaWizyt($d->liczba_rezerwacji) }} w tym dniu – przenieś je albo odwołaj</p>
                    @else
                        <p class="text-xs text-zinc-400 mt-0.5">{{ $kiedy }} · brak rezerwacji</p>
                    @endif
                </div>
                @if ($d->liczba_rezerwacji > 0)
                    <a href="{{ route('pracownik.reservations.plan', ['data' => $d->data->toDateString()]) }}"
                       class="text-[13px] font-medium text-orange-500 hover:text-orange-400 whitespace-nowrap">Pokaż wizyty →</a>
                @endif
                <form method="POST" action="{{ route('admin.days-off.destroy', $d) }}"
                      onsubmit="return confirm('Usunąć dzień wolny {{ $d->data->format('d.m.Y') }}? Dzień znów będzie dostępny do rezerwacji.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-[13px] text-red-400/80 hover:text-red-400">Usuń</button>
                </form>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-sm text-zinc-400">Brak zaplanowanych dni wolnych.</p>
        @endforelse
    </section>
</div>

<script>
(function () {
    const wizyty = @json($wizytyWgDni);
    const form = document.getElementById('form-dnia-wolnego');
    const poleData = document.getElementById('pole-data');
    const ostrzezenie = document.getElementById('ostrzezenie-wizyt');

    function liczba() {
        return parseInt(wizyty[poleData.value] || 0, 10);
    }

    poleData.addEventListener('change', function () {
        const n = liczba();
        ostrzezenie.classList.toggle('hidden', n === 0);
        ostrzezenie.textContent = n > 0
            ? 'W tym dniu jest już ' + n + ' umówionych wizyt. Po zamknięciu warsztatu trzeba je będzie przenieść albo odwołać.'
            : '';
    });

    form.addEventListener('submit', function (e) {
        const n = liczba();
        if (n > 0 && !confirm('W tym dniu jest ' + n + ' umówionych wizyt. Na pewno zamknąć warsztat? Wizyty nie zostaną odwołane automatycznie.')) {
            e.preventDefault();
        }
    });
})();
</script>
@endsection