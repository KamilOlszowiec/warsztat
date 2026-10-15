@extends('layouts.admin')
@section('title', 'Specjalizacje')

@section('content')
@php
    $odmiana = fn ($n, $a, $b, $c) => $n === 1 ? $a : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14) ? $b : $c);
@endphp

<div class="max-w-6xl space-y-5">

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">ZESPÓŁ</p>
            <h1 class="text-2xl font-bold text-zinc-50">Specjalizacje</h1>
            <p class="text-sm text-zinc-400 mt-1.5">Łączą usługi z mechanikami: wizytę dostaje tylko ktoś, kto ma wymaganą specjalizację.</p>
        </div>
        <a href="{{ route('admin.specializations.create') }}"
           class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            + Dodaj specjalizację
        </a>
    </div>

    @error('error')
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-4">
        @foreach ($specjalizacje as $s)
            @php
                $liczbaMech = $s->pracownicy->count();
                $liczbaUslug = $s->uslugi->count();
                $brak = $liczbaMech === 0 && $liczbaUslug > 0;
            @endphp
            <article class="bg-[#1F2128] border {{ $brak ? 'border-red-900' : 'border-zinc-800' }} flex flex-col" style="border-radius: 2px;">
                <div class="px-5 py-4 border-b border-zinc-800">
                    <h2 class="font-semibold text-zinc-50">{{ $s->nazwa }}</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">
                        {{ $liczbaMech }} {{ $odmiana($liczbaMech, 'mechanik', 'mechaników', 'mechaników') }}
                        · {{ $liczbaUslug }} {{ $odmiana($liczbaUslug, 'usługa', 'usługi', 'usług') }}
                    </p>
                </div>

                <div class="px-5 py-3.5 flex-1 space-y-3">
                    @if ($liczbaMech > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($s->pracownicy as $p)
                                <a href="{{ route('admin.users.edit', $p) }}"
                                   class="inline-flex items-center gap-1.5 pl-[3px] pr-2 py-[3px] bg-zinc-800 hover:bg-zinc-700 text-xs text-zinc-200 transition" style="border-radius: 12px;">
                                    <span class="w-5 h-5 flex items-center justify-center bg-zinc-600 text-zinc-50 text-[9px] font-bold" style="border-radius: 50%;">{{ mb_strtoupper(mb_substr($p->imie, 0, 1) . mb_substr($p->nazwisko, 0, 1), 'UTF-8') }}</span>
                                    {{ $p->imie }} {{ $p->nazwisko }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($liczbaUslug > 0)
                        <p class="text-xs leading-relaxed text-zinc-400">{{ $s->uslugi->pluck('nazwa')->implode(' · ') }}</p>
                    @else
                        <p class="text-xs text-zinc-500">Żadna usługa nie wymaga jeszcze tej specjalizacji.</p>
                    @endif

                    @if ($brak)
                        <p class="text-[13px] text-red-300">Nikt nie ma tej specjalizacji – {{ $liczbaUslug === 1 ? 'ta usługa nie ma' : 'te usługi nie mają' }} żadnych wolnych terminów.</p>
                    @elseif ($liczbaMech === 1 && $liczbaUslug > 0)
                        <p class="text-xs text-yellow-400">Tylko jedna osoba – gdy ma wolne, {{ $liczbaUslug === 1 ? 'usługa nie ma' : 'usługi nie mają' }} terminów.</p>
                    @endif
                </div>

                <div class="flex items-center gap-4 px-5 py-3 border-t border-zinc-800 text-[13px]">
                    <a href="{{ route('admin.specializations.edit', $s) }}" class="font-medium text-orange-500 hover:text-orange-400">
                        {{ $brak ? 'Przypisz mechaników →' : 'Edytuj' }}
                    </a>
                    @if ($liczbaUslug === 0)
                        <form method="POST" action="{{ route('admin.specializations.destroy', $s) }}" class="ml-auto"
                              onsubmit="return confirm('Usunąć specjalizację „{{ $s->nazwa }}”?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400/80 hover:text-red-400">Usuń</button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach

        <a href="{{ route('admin.specializations.create') }}"
           class="min-h-[180px] flex flex-col items-center justify-center gap-1.5 border border-dashed border-zinc-600 hover:border-orange-600 text-zinc-300 hover:text-orange-400 text-sm font-medium transition" style="border-radius: 2px;">
            <span class="text-2xl text-zinc-500">+</span>
            Nowa specjalizacja
        </a>
    </div>
</div>
@endsection