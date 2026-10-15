@extends('layouts.admin')
@section('title', 'Panel główny')

@section('content')
@php
    $godzina = now()->hour;
    $powitanie = $godzina < 18 ? 'Dzień dobry' : 'Dobry wieczór';
    $maksTop = $topUslugi->max('liczba') ?: 1;
@endphp

<div class="max-w-5xl space-y-6">

    {{-- Nagłówek --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">PANEL GŁÓWNY</p>
            <h1 class="text-3xl font-bold text-zinc-50">{{ $powitanie }}, {{ auth()->user()->imie }}</h1>
            <p class="text-sm text-zinc-400 mt-1.5">{{ today()->translatedFormat('l, j F Y') }}</p>
        </div>
        <a href="{{ route('pracownik.reservations.plan') }}"
           class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            Otwórz plan dnia
        </a>
    </div>

    {{-- Wymaga uwagi --}}
    <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;" aria-label="Wymaga uwagi">
        <div class="flex items-baseline justify-between px-5 py-4 border-b border-zinc-800">
            <h2 class="font-semibold text-zinc-50">Wymaga uwagi</h2>
            <span class="text-[11px] font-mono text-zinc-400">
                @if (count($alerty) === 0)
                    WSZYSTKO W PORZĄDKU
                @else
                    {{ count($alerty) }} {{ count($alerty) === 1 ? 'SPRAWA' : (count($alerty) < 5 ? 'SPRAWY' : 'SPRAW') }}
                @endif
            </span>
        </div>

        @forelse ($alerty as $a)
            <div class="flex items-center gap-3.5 px-5 py-3.5 {{ $loop->last ? '' : 'border-b border-zinc-800' }}">
                <span class="w-2 h-2 shrink-0 {{ $a['pilne'] ? 'bg-red-400' : 'bg-yellow-400' }}" style="border-radius: 50%;"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-zinc-100">{{ $a['tytul'] }}</p>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ $a['opis'] }}</p>
                </div>
                <a href="{{ $a['link'] }}" class="text-[13px] font-semibold text-orange-500 hover:text-orange-400 whitespace-nowrap">{{ $a['akcja'] }} →</a>
            </div>
        @empty
            <div class="flex items-center gap-3 px-5 py-5">
                <span class="w-2 h-2 shrink-0 bg-green-400" style="border-radius: 50%;"></span>
                <p class="text-sm text-zinc-300">Nic nie czeka na reakcję – grafik, usługi i rezerwacje są w porządku.</p>
            </div>
        @endforelse
    </section>

    {{-- Liczby --}}
    <section class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4" aria-label="Liczby">
        <div class="bg-[#1F2128] border border-zinc-800 px-5 py-4" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">DZIŚ</p>
            <p class="text-3xl font-bold text-zinc-50 mt-1.5">{{ $liczby['dzisWizyt'] }} <span class="text-sm font-medium text-zinc-400">wizyt</span></p>
            <p class="text-[13px] mt-0.5 {{ $liczby['dzisOczekujacych'] ? 'text-yellow-400' : 'text-zinc-400' }}">
                {{ $liczby['dzisOczekujacych'] ? $liczby['dzisOczekujacych'] . ' oczekujące' : 'wszystkie potwierdzone' }}
            </p>
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 px-5 py-4" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">TEN TYDZIEŃ</p>
            <p class="text-3xl font-bold text-zinc-50 mt-1.5">{{ $liczby['tydzienWizyt'] }} <span class="text-sm font-medium text-zinc-400">wizyt</span></p>
            <p class="text-[13px] text-zinc-400 mt-0.5">{{ $liczby['tydzienZakonczonych'] }} już zakończonych</p>
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 px-5 py-4" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">OBŁOŻENIE ZESPOŁU</p>
            <p class="text-3xl font-bold text-zinc-50 mt-1.5">{{ $liczby['oblozenieProcent'] !== null ? $liczby['oblozenieProcent'] . '%' : '—' }}</p>
            <p class="text-[13px] text-zinc-400 mt-0.5">{{ $liczby['oblozenieGodziny'] }} zmian w tym tygodniu</p>
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 px-5 py-4" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">PRZYCHÓD · {{ mb_strtoupper($liczby['miesiac'], 'UTF-8') }}</p>
            <p class="text-3xl font-bold text-zinc-50 mt-1.5">{{ number_format($liczby['przychod'], 0, ',', ' ') }} <span class="text-sm font-medium text-zinc-400">zł</span></p>
            <p class="text-[13px] text-zinc-400 mt-0.5">z zakończonych wizyt, wg cennika</p>
        </div>
    </section>

    <div class="flex flex-wrap gap-6 items-start">

        {{-- Obłożenie mechaników --}}
        <section class="flex-[1_1_400px] min-w-0 bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;" aria-label="Obłożenie mechaników">
            <div class="flex items-baseline justify-between px-5 py-4 border-b border-zinc-800">
                <h2 class="font-semibold text-zinc-50">Obłożenie w tym tygodniu</h2>
                <a href="{{ route('admin.shifts.index') }}" class="text-[13px] text-orange-500 hover:text-orange-400">Grafik →</a>
            </div>
            <div class="px-5 py-4 space-y-4">
                @forelse ($oblozenie as $m)
                    @php
                        $proc = $m['procent'];
                        $kolor = $proc === null ? 'bg-zinc-600' : ($proc >= 90 ? 'bg-red-400' : 'bg-orange-600');
                    @endphp
                    <div>
                        <div class="flex justify-between gap-3 mb-1.5 text-sm">
                            <span class="text-zinc-100">{{ $m['nazwa'] }}</span>
                            <span class="text-xs font-mono {{ $proc !== null && $proc >= 90 ? 'text-red-300' : 'text-zinc-300' }}">
                                @if ($proc === null)
                                    brak zmian w tym tygodniu
                                @else
                                    {{ round($m['zajeteMin'] / 60, 1) }} / {{ round($m['zmianyMin'] / 60, 1) }} h · {{ $proc }}%
                                @endif
                            </span>
                        </div>
                        <div class="h-2 bg-zinc-800 overflow-hidden" style="border-radius: 4px;">
                            <div class="h-full {{ $kolor }}" style="width: {{ min($proc ?? 0, 100) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">Brak pracowników w systemie.</p>
                @endforelse
            </div>
        </section>

        {{-- Najczęstsze usługi --}}
        <section class="flex-[1_1_340px] min-w-0 bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;" aria-label="Najczęstsze usługi">
            <div class="flex items-baseline justify-between px-5 py-4 border-b border-zinc-800">
                <h2 class="font-semibold text-zinc-50">Najczęstsze usługi</h2>
                <span class="text-[11px] font-mono text-zinc-400">OSTATNIE 30 DNI</span>
            </div>
            <div class="px-5 py-4 space-y-3.5">
                @forelse ($topUslugi as $u)
                    <div>
                        <div class="flex justify-between mb-1.5 text-sm">
                            <span class="text-zinc-100">{{ $u->nazwa }}</span>
                            <span class="text-xs font-mono text-zinc-300">{{ $u->liczba }}</span>
                        </div>
                        <div class="h-1.5 bg-zinc-800 overflow-hidden" style="border-radius: 3px;">
                            <div class="h-full bg-zinc-500" style="width: {{ round($u->liczba / $maksTop * 100) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">W ostatnich 30 dniach nie było rezerwacji.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection