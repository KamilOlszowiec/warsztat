@extends('layouts.admin')
@section('title', 'Usługi')

@section('content')
@php
    $maks = max($uslugi->max('wizyty_30_dni') ?? 0, 1);
    $czasOpis = function ($min) {
        $h = intdiv($min, 60);
        $m = $min % 60;
        return trim(($h ? $h . ' h ' : '') . ($m ? $m . ' min' : ''));
    };
@endphp

<div class="max-w-6xl space-y-5">

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">OFERTA</p>
            <h1 class="text-2xl font-bold text-zinc-50">Usługi</h1>
        </div>
        <a href="{{ route('admin.services.create') }}"
           class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            + Dodaj usługę
        </a>
    </div>

    @error('error')
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    <nav class="flex gap-1 border-b border-zinc-800 max-w-full overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [&>a]:shrink-0 [&>a]:whitespace-nowrap" aria-label="Widoczność usług">
        @foreach (['aktywne' => 'Aktywne', 'ukryte' => 'Ukryte'] as $klucz => $nazwa)
            <a href="{{ route('admin.services.index', $klucz === 'ukryte' ? ['widok' => 'ukryte'] : []) }}"
               @if ($widok === $klucz) aria-current="page" @endif
               class="inline-flex items-center gap-1.5 min-h-[44px] px-3.5 -mb-px border-b-2 text-sm transition
                      {{ $widok === $klucz ? 'border-orange-600 text-zinc-50 font-semibold' : 'border-transparent text-zinc-400 hover:text-zinc-200 font-medium' }}">
                {{ $nazwa }}
                <span class="px-1.5 min-w-[20px] text-center text-[11px] {{ $widok === $klucz ? 'bg-zinc-700 text-zinc-50' : 'bg-zinc-800 text-zinc-300' }}" style="border-radius: 10px;">{{ $liczby[$klucz] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="relative bg-[#1F2128] border border-zinc-800 overflow-x-auto" style="border-radius: 2px;">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left">
                    <th class="px-4 py-3 font-mono text-[11px] font-normal text-zinc-400">USŁUGA</th>
                    <th class="px-3 py-3 font-mono text-[11px] font-normal text-zinc-400">CZAS</th>
                    <th class="px-3 py-3 font-mono text-[11px] font-normal text-zinc-400 text-right">CENA</th>
                    <th class="px-3 py-3 font-mono text-[11px] font-normal text-zinc-400">WYKONUJĄ</th>
                    <th class="px-3 py-3 font-mono text-[11px] font-normal text-zinc-400 text-right">30 DNI</th>
                    <th class="px-4 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($uslugi as $u)
                    @php
                        $mechanicy = $u->specjalizacja ? $u->specjalizacja->pracownicy : $wszyscyMechanicy;
                    @endphp
                    <tr class="border-b border-zinc-800/60 hover:bg-white/[0.02]">
                        <td class="px-4 py-3.5 max-w-[320px]">
                            <a href="{{ route('admin.services.edit', $u) }}" class="font-semibold text-zinc-50 hover:text-orange-400">{{ $u->nazwa }}</a>
                            @if ($u->opis)
                                <p class="text-xs text-zinc-400 mt-0.5 truncate">{{ $u->opis }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-3.5 font-mono text-[13px] text-zinc-300 whitespace-nowrap">{{ $czasOpis($u->czas_trwania) }}</td>
                        <td class="px-3 py-3.5 font-semibold text-zinc-50 text-right whitespace-nowrap">{{ number_format($u->cena, 2, ',', ' ') }} zł</td>
                        <td class="px-3 py-3.5">
                            <span class="inline-block mb-1.5 text-[11px] px-2 py-0.5 border border-zinc-700 text-zinc-300" style="border-radius: 2px;">
                                {{ $u->specjalizacja->nazwa ?? 'Dowolna' }}
                            </span>
                            @if ($mechanicy->isEmpty())
                                <p class="text-xs text-red-400">Nikt nie wykona – brak terminów</p>
                            @else
                                <div class="flex items-center">
                                    @foreach ($mechanicy as $m)
                                        <span title="{{ $m->imie }} {{ $m->nazwisko }}"
                                              class="w-[26px] h-[26px] inline-flex items-center justify-center text-[10px] font-bold text-zinc-50 bg-zinc-600 border-2 border-[#1F2128] {{ $loop->first ? '' : '-ml-2' }}"
                                              style="border-radius: 50%;">{{ mb_strtoupper(mb_substr($m->imie, 0, 1) . mb_substr($m->nazwisko, 0, 1), 'UTF-8') }}</span>
                                    @endforeach
                                    <span class="ml-2 text-xs text-zinc-400">
                                        {{ $mechanicy->count() === 1 ? $mechanicy->first()->imie . ' ' . $mechanicy->first()->nazwisko : $mechanicy->count() . ' mechaników' }}
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-3.5 text-right">
                            <span class="font-mono text-[13px] text-zinc-200">{{ $u->wizyty_30_dni }}</span>
                            <div class="ml-auto mt-1 w-14 h-1 bg-zinc-800 overflow-hidden" style="border-radius: 2px;">
                                <div class="h-full bg-zinc-500" style="width: {{ round($u->wizyty_30_dni / $maks * 100) }}%;"></div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <a href="{{ route('admin.services.edit', $u) }}" class="text-[13px] font-medium text-orange-500 hover:text-orange-400">Edytuj</a>
                            <form method="POST" action="{{ route('admin.services.toggle', $u) }}" class="inline ml-3">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-[13px] text-zinc-400 hover:text-zinc-100">{{ $u->czy_aktywna ? 'Ukryj' : 'Przywróć' }}</button>
                            </form>
                            @if ($u->rezerwacje_count === 0)
                                <form method="POST" action="{{ route('admin.services.destroy', $u) }}" class="inline ml-3"
                                      onsubmit="return confirm('Usunąć usługę „{{ $u->nazwa }}”? Nie była jeszcze rezerwowana, więc nic nie stracisz.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[13px] text-red-400/80 hover:text-red-400">Usuń</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-400">
                            {{ $widok === 'ukryte' ? 'Nie ma ukrytych usług.' : 'Nie ma jeszcze żadnych usług w ofercie.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-zinc-400">
        @if ($widok === 'aktywne')
            Usług, które były już rezerwowane, nie da się usunąć – ukryj je, a znikną z oferty dla klientów i zostaną w historii wizyt.
        @else
            Ukryte usługi nie są widoczne w kreatorze rezerwacji, ale zostają w historii wizyt. „Przywróć” przenosi je z powrotem do oferty.
        @endif
    </p>
</div>
@endsection