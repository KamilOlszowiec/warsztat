@extends('layouts.klient')
@section('title', 'Szczegóły rezerwacji')

@section('content')
@php
    $kolory = [
        'oczekująca'   => 'bg-yellow-900/40 text-yellow-400',
        'zatwierdzona' => 'bg-green-900/40 text-green-400',
        'odrzucona'    => 'bg-red-900/40 text-red-400',
        'anulowana'    => 'bg-zinc-800 text-zinc-400',
        'zakończona'   => 'bg-blue-900/40 text-blue-400',
    ];
    $opisyStatusow = [
        'oczekująca'   => 'Warsztat jeszcze nie potwierdził wizyty. Dostaniesz e-mail, gdy to zrobi.',
        'zatwierdzona' => 'Wizyta jest potwierdzona. Do zobaczenia w warsztacie!',
        'odrzucona'    => 'Warsztat nie mógł przyjąć tej wizyty.',
        'anulowana'    => 'Ta rezerwacja została anulowana.',
        'zakończona'   => 'Wizyta została zakończona.',
    ];
    $r = $rezerwacja;
    $moznaAnulowac = in_array($r->status, ['oczekująca', 'zatwierdzona']) && $r->termin_rozpoczecia->isFuture();
@endphp

<div class="max-w-4xl">
    <a href="{{ route('klient.reservations.index') }}" class="inline-block text-sm text-zinc-400 hover:text-zinc-200 mb-4">← Moje rezerwacje</a>

    @error('error')
        <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
            {{ $message }}
        </div>
    @enderror

    {{-- Nagłówek --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="min-w-0">
            <p class="text-sm font-mono text-orange-500 mb-1">REZERWACJA #{{ $r->id_rezerwacji }}</p>
            <h1 class="text-2xl font-bold text-zinc-50">{{ $r->nazwy_uslug }}</h1>
            <div class="flex flex-wrap items-center gap-2.5 mt-2">
                <span class="text-[11px] font-mono px-2 py-0.5 {{ $kolory[$r->status] ?? '' }}" style="border-radius: 2px;">
                    {{ mb_strtoupper($r->status, 'UTF-8') }}
                </span>
                <span class="text-sm text-zinc-400">{{ $opisyStatusow[$r->status] ?? '' }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('klient.reservations.create', ['pojazd' => $r->id_pojazdu]) }}"
               class="inline-flex items-center min-h-[44px] px-4 border border-zinc-600 hover:border-zinc-400 text-zinc-200 text-sm font-semibold transition" style="border-radius: 2px;">
                Umów kolejną wizytę
            </a>
            @if ($moznaAnulowac)
                <form method="POST" action="{{ route('klient.reservations.cancel', $r) }}"
                      onsubmit="return confirm('Na pewno anulować tę rezerwację?');">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center min-h-[44px] px-4 border border-red-700 text-red-400 hover:bg-red-700 hover:text-white text-sm font-semibold transition" style="border-radius: 2px;">
                        Anuluj rezerwację
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if ($r->status === 'odrzucona' && $r->powod_odrzucenia)
        <div class="mb-6 bg-red-900/20 border border-red-800 px-5 py-4" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-red-400 mb-1">POWÓD ODRZUCENIA</p>
            <p class="text-sm text-zinc-200">{{ $r->powod_odrzucenia }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6 items-start">

        {{-- Usługi --}}
        <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
            <div class="px-5 py-4 border-b border-zinc-800">
                <h2 class="font-semibold text-zinc-50">Usługi</h2>
            </div>
            @foreach ($r->uslugi as $u)
                <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-zinc-800">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-100">{{ $u->nazwa }}</p>
                        <p class="text-xs font-mono text-zinc-400 mt-0.5">{{ $u->pivot->czas_trwania }} min</p>
                    </div>
                    <p class="text-sm font-semibold text-zinc-100 whitespace-nowrap">{{ number_format($u->pivot->cena, 2, ',', ' ') }} zł</p>
                </div>
            @endforeach
            <div class="flex items-center justify-between gap-4 px-5 py-4">
                <div>
                    <p class="text-sm text-zinc-400">Razem</p>
                    <p class="text-xs text-zinc-500 mt-0.5">ceny z chwili rezerwacji</p>
                </div>
                <p class="text-xl font-bold text-zinc-50">{{ number_format($r->laczna_cena, 2, ',', ' ') }} zł</p>
            </div>
        </section>

        {{-- Szczegóły --}}
        <aside class="bg-[#1F2128] border border-zinc-800 divide-y divide-zinc-800" style="border-radius: 2px;">
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">TERMIN</p>
                <p class="text-zinc-100">{{ $r->termin_rozpoczecia->translatedFormat('l, j F Y') }}</p>
                <p class="text-zinc-50 font-semibold mt-0.5">
                    {{ $r->termin_rozpoczecia->format('H:i') }} – {{ $r->termin_zakonczenia->format('H:i') }}
                    <span class="text-sm font-normal text-zinc-400">({{ $r->laczny_czas }} min)</span>
                </p>
            </div>
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">POJAZD</p>
                <p class="text-zinc-100">{{ $r->pojazd->marka }} {{ $r->pojazd->model }}</p>
                <span class="inline-flex items-stretch border border-zinc-400 overflow-hidden mt-2" style="border-radius: 3px;">
                    <span class="w-2 bg-blue-700"></span>
                    <span class="bg-zinc-100 text-zinc-900 font-mono font-bold text-xs tracking-wider px-1.5 py-0.5">{{ $r->pojazd->numer_rejestracyjny }}</span>
                </span>
            </div>
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">MECHANIK</p>
                <p class="text-zinc-100">{{ $r->mechanik ? $r->mechanik->imie . ' ' . $r->mechanik->nazwisko : '—' }}</p>
            </div>
            @if ($r->uwagi)
                <div class="px-5 py-4">
                    <p class="text-[11px] font-mono text-zinc-400 mb-1">TWOJE UWAGI</p>
                    <p class="text-sm text-zinc-200 whitespace-pre-line">{{ $r->uwagi }}</p>
                </div>
            @endif
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">ZŁOŻONA</p>
                <p class="text-sm text-zinc-300">{{ $r->created_at->format('d.m.Y, H:i') }}</p>
            </div>
        </aside>
    </div>
</div>
@endsection