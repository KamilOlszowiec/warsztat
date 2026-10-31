@extends('layouts.admin')
@section('title', $user->imie . ' ' . $user->nazwisko)

@section('content')
@php
    $inicjaly = mb_strtoupper(mb_substr($user->imie, 0, 1) . mb_substr($user->nazwisko, 0, 1), 'UTF-8');
@endphp

<div class="max-w-6xl space-y-5">
    <a href="{{ route('admin.users.index') }}" class="inline-block text-[13px] text-zinc-400 hover:text-zinc-200">← Użytkownicy</a>

    @error('error')
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    {{-- Nagłówek --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-[52px] h-[52px] flex items-center justify-center {{ $user->czyAdministrator() ? 'bg-orange-600' : 'bg-zinc-600' }} text-zinc-50 text-lg font-bold" style="border-radius: 50%;">{{ $inicjaly }}</div>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-zinc-50">{{ $user->imie }} {{ $user->nazwisko }}</h1>
                    <span class="text-[10px] font-mono px-2 py-0.5 {{ $user->czyAdministrator() ? 'bg-orange-600/20 text-orange-300' : 'bg-zinc-800 text-zinc-200' }}" style="border-radius: 2px;">
                        {{ $user->czyAdministrator() ? 'ADMINISTRATOR' : 'MECHANIK' }}
                    </span>
                </div>
                <p class="text-[13px] text-zinc-400 mt-1">{{ $user->email }} · konto od {{ $user->created_at?->format('d.m.Y') ?? '—' }}</p>
            </div>
        </div>
        <div class="flex gap-2.5">
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center min-h-[44px] px-4 border border-zinc-700 text-zinc-300 hover:text-white text-sm font-medium" style="border-radius: 2px;">Anuluj</a>
            <button type="submit" form="form-uzytkownika"
                    class="min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">Zapisz zmiany</button>
        </div>
    </div>

    <div class="flex flex-wrap gap-5 items-start">

        {{-- Formularz --}}
        <form id="form-uzytkownika" method="POST" action="{{ route('admin.users.update', $user) }}" class="flex-[2_1_460px] min-w-0 space-y-5">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['user' => $user])
        </form>

        {{-- Prawa kolumna --}}
        <div class="flex-[1_1_280px] min-w-0 space-y-5">

            @if ($statystyki)
                <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
                    <div class="flex items-baseline justify-between px-5 py-4 border-b border-zinc-800">
                        <h2 class="text-[15px] font-semibold text-zinc-50">Ten tydzień</h2>
                        <a href="{{ route('admin.shifts.index') }}" class="text-[13px] text-orange-500 hover:text-orange-400">Grafik →</a>
                    </div>
                    <div class="px-5 py-4 space-y-3.5 text-sm">
                        <div>
                            <div class="flex justify-between mb-1.5">
                                <span class="text-zinc-300">Obłożenie</span>
                                <span class="text-xs font-mono {{ ($statystyki['procent'] ?? 0) >= 90 ? 'text-red-300' : 'text-zinc-300' }}">
                                    {{ $statystyki['wizytyGodz'] }} / {{ $statystyki['zmianyGodz'] }} h
                                </span>
                            </div>
                            <div class="h-2 bg-zinc-800 overflow-hidden" style="border-radius: 4px;">
                                <div class="h-full {{ ($statystyki['procent'] ?? 0) >= 90 ? 'bg-red-400' : 'bg-orange-600' }}" style="width: {{ min($statystyki['procent'] ?? 0, 100) }}%;"></div>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-mono text-zinc-400 mb-0.5">NAJBLIŻSZA ZMIANA</p>
                            @if ($statystyki['najblizsza'])
                                @php $z = $statystyki['najblizsza']; @endphp
                                <p class="text-zinc-100">
                                    {{ $z->data->isToday() ? 'dziś' : $z->data->translatedFormat('D, d.m') }} ·
                                    {{ substr($z->godzina_od, 0, 5) }} – {{ substr($z->godzina_do, 0, 5) }}
                                </p>
                            @else
                                <p class="text-zinc-400">Brak zaplanowanych zmian</p>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            @include('admin.users._haslo')

            @if ($user->id_uzytkownika !== auth()->id())
                <section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
                    <h2 class="px-5 py-4 border-b border-zinc-800 text-[15px] font-semibold text-zinc-50">Usunięcie konta</h2>
                    <div class="px-5 py-4">
                        @if ($moznaUsunac)
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('Na pewno usunąć konto {{ $user->imie }} {{ $user->nazwisko }}? Tej operacji nie można cofnąć.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full min-h-[42px] border border-red-800 text-red-400 hover:bg-red-900/30 text-[13px] font-medium transition" style="border-radius: 2px;">
                                    Usuń konto
                                </button>
                            </form>
                        @else
                            <p class="text-[13px] text-zinc-400">Tego konta nie można usunąć, bo pracownik obsługiwał lub wykonywał wizyty – historia napraw musi zostać w systemie.</p>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection