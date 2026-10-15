@extends('layouts.pracownik')
@section('title', 'Mój grafik')

@section('content')
@php
    $nazwyDni = ['poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota', 'niedziela'];
    $odmianaWizyt = fn ($n) => $n === 1 ? 'wizyta' : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14) ? 'wizyty' : 'wizyt');
@endphp

<div class="max-w-3xl">
    <p class="text-sm font-mono text-orange-500 mb-1">GRAFIK</p>
    <h1 class="text-2xl font-bold text-zinc-50 mb-1">Mój grafik</h1>
    <p class="text-sm text-zinc-400 mb-6">Najbliższe 4 tygodnie. Zmiany w grafiku wprowadza administrator.</p>

    <div class="space-y-6">
        @foreach ($tygodnie as $tydzien)
            <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
                <div class="flex items-baseline justify-between gap-4 px-5 py-3.5 border-b border-zinc-800">
                    <h2 class="font-semibold text-zinc-50">
                        {{ $tydzien['poczatek']->format('d.m') }} – {{ $tydzien['koniec']->format('d.m.Y') }}
                    </h2>
                    <span class="text-sm font-mono text-zinc-400">
                        {{ rtrim(rtrim(number_format($tydzien['godziny'], 1, ',', ''), '0'), ',') }} h
                    </span>
                </div>

                @foreach ($tydzien['dni'] as $dzien)
                    @php $dzis = $dzien['data']->isToday(); @endphp
                    <div class="flex items-center gap-4 px-5 py-3 border-b border-zinc-800/60 last:border-b-0 {{ $dzis ? 'bg-orange-600/10' : '' }}">
                        <div class="w-36 shrink-0">
                            <p class="text-sm {{ $dzis ? 'text-orange-400 font-semibold' : 'text-zinc-200' }}">
                                {{ $nazwyDni[$dzien['data']->dayOfWeekIso - 1] }}
                                @if ($dzis) <span class="text-[10px] font-mono ml-1">DZIŚ</span> @endif
                            </p>
                            <p class="text-xs font-mono text-zinc-500">{{ $dzien['data']->format('d.m.Y') }}</p>
                        </div>

                        <div class="flex-1 min-w-0">
                            @if ($dzien['dzienWolny'])
                                <p class="text-sm text-zinc-400">Dzień wolny warsztatu</p>
                                @if ($dzien['dzienWolny']->opis)
                                    <p class="text-xs text-zinc-500">{{ $dzien['dzienWolny']->opis }}</p>
                                @endif
                            @elseif ($dzien['zmiana'])
                                <p class="text-sm font-mono font-semibold {{ $dzien['data']->isPast() && !$dzis ? 'text-zinc-400' : 'text-zinc-50' }}">
                                    {{ substr($dzien['zmiana']->godzina_od, 0, 5) }} – {{ substr($dzien['zmiana']->godzina_do, 0, 5) }}
                                </p>
                            @elseif (!$dzien['czynne'])
                                <p class="text-sm text-zinc-600">Warsztat nieczynny</p>
                            @else
                                <p class="text-sm text-zinc-500">Wolne</p>
                            @endif
                        </div>

                        @if ($dzien['wizyty'] > 0)
                            <a href="{{ route('pracownik.reservations.plan', ['data' => $dzien['data']->toDateString()]) }}"
                               class="shrink-0 text-xs px-2 py-0.5 bg-zinc-800 text-zinc-300 hover:text-orange-400 hover:bg-zinc-700 transition" style="border-radius: 2px;">
                                {{ $dzien['wizyty'] }} {{ $odmianaWizyt($dzien['wizyty']) }} →
                            </a>
                        @endif
                    </div>
                @endforeach
            </section>
        @endforeach
    </div>
</div>
@endsection