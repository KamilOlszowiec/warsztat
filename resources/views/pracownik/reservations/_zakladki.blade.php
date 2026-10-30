@php
    // Pracownik widzi licznik swoich oczekujących wizyt, administrator – wszystkich
    $liczbaOczekujacychZakladka = \App\Models\Rezerwacja::where('status', 'oczekująca')
        ->where('termin_rozpoczecia', '>', now())
        ->when(auth()->user()->rola !== 'administrator', fn ($q) => $q->where('id_mechanika', auth()->id()))
        ->count();
@endphp

<div class="flex flex-wrap items-end justify-between gap-4 mb-5">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">REZERWACJE</p>
        <h1 class="text-2xl font-bold text-zinc-50">{{ $tytul }}</h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('pracownik.reservations.nowa') }}"
       class="inline-flex items-center min-h-[44px] px-4 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
        + Nowa wizyta
    </a>
    <nav class="flex bg-[#1F2128] border border-zinc-800 p-[3px]" style="border-radius: 2px;" aria-label="Widok rezerwacji">
        <a href="{{ route('pracownik.reservations.plan') }}"
           @if ($aktywna === 'plan') aria-current="page" @endif
           class="inline-flex items-center min-h-[38px] px-4 text-sm transition {{ $aktywna === 'plan' ? 'bg-orange-600 text-white font-semibold' : 'text-zinc-300 hover:text-white font-medium' }}" style="border-radius: 2px;">
            Plan dnia
        </a>
        <a href="{{ route('pracownik.reservations.index') }}"
           @if ($aktywna === 'lista') aria-current="page" @endif
           class="inline-flex items-center gap-1.5 min-h-[38px] px-4 text-sm transition {{ $aktywna === 'lista' ? 'bg-orange-600 text-white font-semibold' : 'text-zinc-300 hover:text-white font-medium' }}" style="border-radius: 2px;">
            Lista
            @if ($liczbaOczekujacychZakladka > 0)
                <span class="px-1.5 text-[11px] bg-yellow-900 text-yellow-200" style="border-radius: 10px;">{{ $liczbaOczekujacychZakladka }}</span>
            @endif
        </a>
    </nav>
    </div>
</div>
