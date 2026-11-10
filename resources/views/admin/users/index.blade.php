@extends('layouts.admin')
@section('title', 'Użytkownicy')

@section('content')
@php
    $odmiana = fn ($n, $a, $b, $c) => $n === 1 ? $a : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14) ? $b : $c);
    $zakladki = ['wszyscy' => 'Wszyscy', 'pracownik' => 'Pracownicy', 'klient' => 'Klienci', 'administrator' => 'Administratorzy'];
    $liczba = fn ($k) => $k === 'wszyscy' ? $liczby->sum() : (int) ($liczby[$k] ?? 0);
    $role = [
        'administrator' => ['ADMINISTRATOR', 'bg-orange-600/20 text-orange-300', 'bg-orange-600'],
        'pracownik'     => ['MECHANIK', 'bg-zinc-800 text-zinc-200', 'bg-zinc-600'],
        'klient'        => ['KLIENT', 'border border-zinc-700 text-zinc-400', 'bg-zinc-700'],
    ];
@endphp

<div class="max-w-6xl space-y-5">

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">ZESPÓŁ</p>
            <h1 class="text-2xl font-bold text-zinc-50">Użytkownicy</h1>
        </div>
        <a href="{{ route('admin.users.create') }}"
           class="inline-flex items-center min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
            + Dodaj pracownika
        </a>
    </div>

    @error('error')
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-800">
        <nav class="flex gap-1 max-w-full overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Filtr ról">
            @foreach ($zakladki as $klucz => $nazwa)
                <a href="{{ route('admin.users.index', array_filter(['rola' => $klucz === 'wszyscy' ? null : $klucz, 'q' => $q])) }}"
                   @if ($filtr === $klucz) aria-current="page" @endif
                   class="inline-flex shrink-0 whitespace-nowrap items-center gap-1.5 min-h-[44px] px-3.5 -mb-px border-b-2 text-sm transition
                          {{ $filtr === $klucz ? 'border-orange-600 text-zinc-50 font-semibold' : 'border-transparent text-zinc-400 hover:text-zinc-200 font-medium' }}">
                    {{ $nazwa }}
                    <span class="px-1.5 min-w-[20px] text-center text-[11px] {{ $filtr === $klucz ? 'bg-zinc-700 text-zinc-50' : 'bg-zinc-800 text-zinc-300' }}" style="border-radius: 10px;">{{ $liczba($klucz) }}</span>
                </a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-2 flex items-center gap-2 px-3 h-[38px] bg-[#1F2128] border border-zinc-700 focus-within:border-orange-600" style="border-radius: 2px;">
            @if ($filtr !== 'wszyscy') <input type="hidden" name="rola" value="{{ $filtr }}"> @endif
            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-zinc-400"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
            <label for="szukaj" class="sr-only">Szukaj użytkownika</label>
            <input id="szukaj" type="search" name="q" value="{{ $q }}" placeholder="Szukaj po nazwisku lub e-mailu"
                   class="w-56 bg-transparent text-sm text-zinc-50 placeholder-zinc-500 focus:outline-none">
        </form>
    </div>

    <div class="relative bg-[#1F2128] border border-zinc-800 overflow-x-auto" style="border-radius: 2px;">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left">
                    <th class="px-4 py-3 font-mono text-[11px] font-normal text-zinc-400">UŻYTKOWNIK</th>
                    <th class="px-4 py-3 font-mono text-[11px] font-normal text-zinc-400">ROLA</th>
                    <th class="px-4 py-3 font-mono text-[11px] font-normal text-zinc-400">SZCZEGÓŁY</th>
                    <th class="px-4 py-3 font-mono text-[11px] font-normal text-zinc-400">KONTO</th>
                    <th class="px-4 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    @php
                        [$rolaNazwa, $rolaStyl, $awatarStyl] = $role[$u->rola] ?? ['?', '', 'bg-zinc-700'];
                        $inicjaly = mb_strtoupper(mb_substr($u->imie, 0, 1) . mb_substr($u->nazwisko, 0, 1), 'UTF-8');
                    @endphp
                    <tr class="border-b border-zinc-800/60 hover:bg-white/[0.02]">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="w-[34px] h-[34px] shrink-0 flex items-center justify-center text-xs font-bold text-zinc-50 {{ $awatarStyl }}" style="border-radius: 50%;">{{ $inicjaly }}</span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-zinc-50">{{ $u->imie }} {{ $u->nazwisko }}</p>
                                    <p class="text-xs text-zinc-400">{{ $u->email ?? ($u->telefon ? 'tel. ' . $u->telefon : '—') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[10px] font-mono px-2 py-0.5 {{ $rolaStyl }}" style="border-radius: 2px;">{{ $rolaNazwa }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($u->czyPracownik())
                                @forelse ($u->specjalizacje as $s)
                                    <span class="inline-block mr-1 mb-1 text-[11px] px-2 py-0.5 border border-zinc-700 text-zinc-300" style="border-radius: 2px;">{{ $s->nazwa }}</span>
                                @empty
                                    <span class="text-xs text-red-400">Brak specjalizacji – nie dostanie żadnej wizyty</span>
                                @endforelse
                            @elseif ($u->czyKlient())
                                <span class="text-xs text-zinc-400">
                                    {{ $u->pojazdy_count }} {{ $odmiana($u->pojazdy_count, 'pojazd', 'pojazdy', 'pojazdów') }}
                                    · {{ $u->rezerwacje_klienta_count }} {{ $odmiana($u->rezerwacje_klienta_count, 'wizyta', 'wizyty', 'wizyt') }}
                                </span>
                            @else
                                <span class="text-xs text-zinc-400">Pełny dostęp</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if (!$u->email)
                                <span class="text-[10px] font-mono px-2 py-0.5 bg-zinc-800 text-zinc-300" style="border-radius: 2px;">BEZ E-MAILA</span>
                            @elseif ($u->email_verified_at)
                                <span class="text-[10px] font-mono px-2 py-0.5 bg-green-900/40 text-green-400" style="border-radius: 2px;">AKTYWNE</span>
                            @else
                                <span class="text-[10px] font-mono px-2 py-0.5 bg-yellow-900/40 text-yellow-400" style="border-radius: 2px;">NIEZWERYFIKOWANE</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($u->czyKlient())
                                <a href="{{ route('admin.users.show', $u) }}" class="text-[13px] font-medium text-orange-500 hover:text-orange-400">Karta klienta →</a>
                            @else
                                <a href="{{ route('admin.users.edit', $u) }}" class="text-[13px] font-medium text-orange-500 hover:text-orange-400">Edytuj</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-400">
                            {{ $q !== '' ? 'Brak użytkowników pasujących do „' . $q . '”.' : 'Brak użytkowników w tej kategorii.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $users->links() }}</div>
</div>
@endsection