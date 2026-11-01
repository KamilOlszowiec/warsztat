@extends('layouts.admin')
@section('title', $user->imie . ' ' . $user->nazwisko)

@section('content')
@php
    $inicjaly = mb_strtoupper(mb_substr($user->imie, 0, 1) . mb_substr($user->nazwisko, 0, 1), 'UTF-8');
    $jedenPojazd = $user->pojazdy->count() === 1;
@endphp

<div class="max-w-6xl space-y-5">
    <a href="{{ route('admin.users.index', ['rola' => 'klient']) }}" class="inline-block text-[13px] text-zinc-400 hover:text-zinc-200">← Użytkownicy</a>

    @error('error')
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    {{-- Nagłówek --}}
    <div class="flex items-center gap-3.5">
        <div class="w-[52px] h-[52px] flex items-center justify-center bg-zinc-700 text-zinc-50 text-lg font-bold" style="border-radius: 50%;">{{ $inicjaly }}</div>
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-zinc-50">{{ $user->imie }} {{ $user->nazwisko }}</h1>
                <span class="text-[10px] font-mono px-2 py-0.5 border border-zinc-700 text-zinc-400" style="border-radius: 2px;">KLIENT</span>
                @if (!$user->email)
                    <span class="text-[10px] font-mono px-2 py-0.5 bg-zinc-800 text-zinc-300" style="border-radius: 2px;">BEZ E-MAILA</span>
                @elseif ($user->email_verified_at)
                    <span class="text-[10px] font-mono px-2 py-0.5 bg-green-900/40 text-green-400" style="border-radius: 2px;">AKTYWNE</span>
                @else
                    <span class="text-[10px] font-mono px-2 py-0.5 bg-yellow-900/40 text-yellow-400" style="border-radius: 2px;">NIEZWERYFIKOWANE</span>
                @endif
            </div>
            <p class="text-[13px] text-zinc-400 mt-1">Klient od {{ $user->created_at?->format('d.m.Y') ?? '—' }}</p>
        </div>
    </div>

    {{-- Liczby --}}
    <section class="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-3.5">
        <div class="bg-[#1F2128] border border-zinc-800 px-4 py-3.5" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">POJAZDY</p>
            <p class="text-2xl font-bold text-zinc-50 mt-1">{{ $user->pojazdy->count() }}</p>
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 px-4 py-3.5" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">WIZYTY</p>
            <p class="text-2xl font-bold text-zinc-50 mt-1">{{ $liczbaWizyt }}</p>
            <p class="text-xs text-zinc-400">{{ $zaplanowane }} zaplanowane, {{ $anulowane }} anulowane</p>
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 px-4 py-3.5" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">WYDAŁ W WARSZTACIE</p>
            <p class="text-2xl font-bold text-zinc-50 mt-1">{{ number_format($wydal, 0, ',', ' ') }} <span class="text-sm font-medium text-zinc-400">zł</span></p>
            <p class="text-xs text-zinc-400">z zakończonych wizyt</p>
        </div>
        <div class="bg-[#1F2128] border border-zinc-800 px-4 py-3.5" style="border-radius: 2px;">
            <p class="text-[11px] font-mono text-zinc-400">NAJBLIŻSZA WIZYTA</p>
            @if ($najblizsza)
                <p class="text-lg font-bold text-orange-400 mt-1">{{ $najblizsza->termin_rozpoczecia->translatedFormat('D, d.m') }} · {{ $najblizsza->termin_rozpoczecia->format('H:i') }}</p>
                <p class="text-xs text-zinc-400 truncate">{{ $najblizsza->nazwy_uslug }}</p>
            @else
                <p class="text-lg font-bold text-zinc-500 mt-1">—</p>
            @endif
        </div>
    </section>

    <div class="flex flex-wrap gap-5 items-start">

        {{-- Pojazdy z historią --}}
        <section class="flex-[2_1_460px] min-w-0 space-y-3" aria-label="Pojazdy klienta">
            @forelse ($user->pojazdy as $p)
                @php
                    $historia = $p->rezerwacje;
                    $ostatnia = $historia->firstWhere('status', 'zakończona');
                    $najblizszaAuta = $historia
                        ->filter(fn ($r) => in_array($r->status, ['oczekująca', 'zatwierdzona']) && $r->termin_rozpoczecia->isFuture())
                        ->sortBy('termin_rozpoczecia')->first();
                @endphp
                <details class="group bg-[#1F2128] border border-zinc-800 open:border-zinc-700" style="border-radius: 2px;" @if ($jedenPojazd || $loop->first) open @endif>
                    <summary class="list-none cursor-pointer px-5 py-4 [&::-webkit-details-marker]:hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-zinc-50">{{ $p->marka }} {{ $p->model }}</p>
                                <p class="text-[11px] font-mono text-zinc-400 mt-0.5">{{ $p->rok_produkcji ?? '—' }}@if ($p->vin) · VIN {{ $p->vin }}@endif</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-stretch border border-zinc-400 overflow-hidden" style="border-radius: 3px;">
                                    <span class="w-2 bg-blue-700"></span>
                                    <span class="bg-zinc-100 text-zinc-900 font-mono font-bold text-sm tracking-wider px-2 py-0.5">{{ $p->numer_rejestracyjny }}</span>
                                </span>
                                <span class="text-zinc-400 transition-transform group-open:rotate-180" aria-hidden="true">▾</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 mt-3 text-xs">
                            <div><p class="font-mono text-zinc-500 mb-0.5">WIZYTY</p><p class="text-zinc-100 font-semibold">{{ $historia->count() }}</p></div>
                            <div><p class="font-mono text-zinc-500 mb-0.5">OSTATNIA</p><p class="text-zinc-200">{{ $ostatnia ? $ostatnia->termin_rozpoczecia->format('d.m.Y') : '—' }}</p></div>
                            <div><p class="font-mono text-zinc-500 mb-0.5">NAJBLIŻSZA</p>
                                <p class="{{ $najblizszaAuta ? 'text-orange-400 font-medium' : 'text-zinc-200' }}">{{ $najblizszaAuta ? $najblizszaAuta->termin_rozpoczecia->format('d.m · H:i') : '—' }}</p>
                            </div>
                        </div>
                    </summary>
                    <div class="px-5 pb-5 pt-1 border-t border-zinc-800">
                        <p class="text-[11px] font-mono text-zinc-400 mt-4 mb-3">HISTORIA WIZYT</p>
                        @if ($historia->isEmpty())
                            <p class="text-sm text-zinc-500">Ten pojazd nie był jeszcze w warsztacie.</p>
                        @else
                            <ol class="relative border-l border-zinc-700 ml-1.5">
                                @foreach ($historia as $r)
                                    <li class="relative pl-5 {{ $loop->last ? '' : 'pb-4' }}">
                                        <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 {{ $r->statusEnum()->kropka() }}" style="border-radius: 50%;"></span>
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs font-mono text-zinc-300">{{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}</span>
                                            <x-status :status="$r->status" rozmiar="maly" />
                                        </div>
                                        <p class="text-sm text-zinc-100 mt-1">{{ $r->nazwy_uslug }}</p>
                                        <p class="text-xs text-zinc-400 mt-0.5">
                                            @if ($r->status === 'odrzucona' && $r->powod_odrzucenia)
                                                Powód: {{ $r->powod_odrzucenia }}
                                            @else
                                                {{ number_format($r->status === 'zakończona' ? $r->kwota_koncowa : $r->laczna_cena, 2, ',', ' ') }} zł
                                                @if ($r->mechanik) · {{ $r->mechanik->imie }} {{ $r->mechanik->nazwisko }} @endif
                                            @endif
                                        </p>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </details>
            @empty
                <div class="bg-[#1F2128] border border-dashed border-zinc-700 p-8 text-center text-sm text-zinc-400" style="border-radius: 2px;">
                    Klient nie dodał jeszcze żadnego pojazdu.
                </div>
            @endforelse
        </section>

        {{-- Kontakt i konto --}}
        <div class="flex-[1_1_280px] min-w-0 space-y-5">
            <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
                <h2 class="px-5 py-4 border-b border-zinc-800 text-[15px] font-semibold text-zinc-50">Kontakt</h2>
                <dl class="px-5 py-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-[11px] font-mono text-zinc-400">TELEFON</dt>
                        <dd class="mt-0.5">
                            @if ($user->telefon)
                                <a href="tel:{{ str_replace(' ', '', $user->telefon) }}" class="text-zinc-100 hover:text-orange-400">{{ $user->telefon }}</a>
                            @else
                                <span class="text-zinc-500">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-mono text-zinc-400">E-MAIL</dt>
                        <dd class="mt-0.5">
                            @if ($user->email)
                                <a href="mailto:{{ $user->email }}" class="text-zinc-100 hover:text-orange-400 break-all">{{ $user->email }}</a>
                            @else
                                <span class="text-zinc-500">nie podano</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
                <h2 class="px-5 py-4 border-b border-zinc-800 text-[15px] font-semibold text-zinc-50">Konto</h2>
                <div class="px-5 py-4 space-y-2.5">
                    @if (!$user->email)
                        <div class="flex gap-2.5 items-start px-3 py-2.5 bg-zinc-800/60 border border-zinc-700" style="border-radius: 2px;">
                            <span class="w-2 h-2 shrink-0 mt-1.5 bg-zinc-400" style="border-radius: 50%;"></span>
                            <p class="text-[13px] text-zinc-200">Klient zapisany telefonicznie, bez adresu e-mail – nie loguje się do aplikacji i nie dostaje powiadomień. Wizyty umawia z nim warsztat.</p>
                        </div>
                    @elseif (!$user->email_verified_at)
                        <div class="flex gap-2.5 items-start px-3 py-2.5 bg-yellow-900/20 border border-yellow-700/40" style="border-radius: 2px;">
                            <span class="w-2 h-2 shrink-0 mt-1.5 bg-yellow-400" style="border-radius: 50%;"></span>
                            <p class="text-[13px] text-zinc-200">Klient nie potwierdził adresu e-mail – nie może składać nowych rezerwacji.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.users.verification', $user) }}">
                            @csrf
                            <button type="submit" class="w-full min-h-[42px] border border-zinc-700 text-zinc-200 hover:text-white hover:border-zinc-500 text-[13px] font-medium transition" style="border-radius: 2px;">
                                Wyślij ponownie link weryfikacyjny
                            </button>
                        </form>
                    @endif

                    <p class="text-xs text-zinc-400 pt-1">Dane osobowe i pojazdy zmienia klient w swoim panelu.</p>

                    @if ($moznaUsunac)
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="pt-2"
                              onsubmit="return confirm('Na pewno usunąć konto klienta {{ $user->imie }} {{ $user->nazwisko }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full min-h-[42px] border border-red-800 text-red-400 hover:bg-red-900/30 text-[13px] font-medium transition" style="border-radius: 2px;">Usuń konto</button>
                        </form>
                    @endif
                </div>
            </section>
            @if ($user->email)
                @include('admin.users._haslo')
            @endif
        </div>
    </div>
</div>
@endsection