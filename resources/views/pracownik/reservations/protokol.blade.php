@extends(auth()->user()->czyAdministrator() ? 'layouts.admin' : 'layouts.pracownik')
@section('title', $nowy ? 'Zakończ wizytę' : 'Popraw protokół')

@section('content')
@php
    $r = $rezerwacja;
    $pole = 'w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600';
    $etykieta = 'block text-xs font-mono text-zinc-400 mb-1.5';
@endphp

<div class="max-w-4xl">
    <a href="{{ route('pracownik.reservations.plan', ['data' => $r->termin_rozpoczecia->toDateString(), 'wizyta' => $r->id_rezerwacji]) }}"
       class="inline-block text-sm text-zinc-400 hover:text-zinc-200 mb-4">← Plan dnia</a>

    <p class="text-sm font-mono text-orange-500 mb-1">{{ $nowy ? 'ZAKOŃCZENIE WIZYTY' : 'POPRAWA PROTOKOŁU' }}</p>
    <h1 class="text-2xl font-bold text-zinc-50 mb-1">Protokół z wizyty</h1>
    <p class="text-sm text-zinc-400 mb-6">
        @if ($nowy)
            Protokół trafi do historii pojazdu, a klient otrzyma e-mail z podsumowaniem wizyty.
        @else
            Klient zobaczy poprawioną wersję w historii pojazdu. Ponowny e-mail nie zostanie wysłany.
        @endif
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6 items-start">
        <form method="POST" action="{{ route('pracownik.reservations.protokol.update', $r) }}"
              class="bg-[#1F2128] border border-zinc-800 p-6 space-y-5" style="border-radius: 2px;">
            @csrf
            @method('PUT')

            <div>
                <label for="przebieg" class="{{ $etykieta }}">PRZEBIEG (KM, OPCJONALNIE)</label>
                <input type="number" id="przebieg" name="przebieg" min="0" max="3000000"
                       value="{{ old('przebieg', $protokol?->przebieg) }}" class="{{ $pole }} max-w-[220px]" style="border-radius: 2px;">
                @if ($poprzedniPomiar)
                    <p class="text-xs text-zinc-500 mt-1.5">
                        Ostatni zapisany przebieg ({{ $poprzedniPomiar->termin_rozpoczecia->format('d.m.Y') }}):
                        {{ number_format($poprzedniPomiar->protokol->przebieg, 0, ',', ' ') }} km
                    </p>
                @else
                    <p class="text-xs text-zinc-500 mt-1.5">Warto podać np. przy przeglądzie lub wymianie oleju.</p>
                @endif
                @error('przebieg') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                @if (session('pokaz_potwierdzenie'))
                    <label class="flex items-center gap-2 mt-2 text-sm text-zinc-300 cursor-pointer">
                        <input type="checkbox" name="potwierdz_przebieg" value="1" class="accent-orange-600">
                        Potwierdzam – przebieg jest prawidłowy (np. wymieniony licznik)
                    </label>
                @endif
            </div>

            <div>
                <label for="opis_prac" class="{{ $etykieta }}">WYKONANE PRACE</label>
                <textarea id="opis_prac" name="opis_prac" rows="4" maxlength="2000" required
                          placeholder="np. Wymiana oleju 5W-30 i filtra oleju, kontrola poziomu płynów"
                          class="{{ $pole }}" style="border-radius: 2px;">{{ old('opis_prac', $protokol?->opis_prac) }}</textarea>
                @error('opis_prac') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="zalecenia" class="{{ $etykieta }}">ZALECENIA (OPCJONALNIE)</label>
                <textarea id="zalecenia" name="zalecenia" rows="3" maxlength="2000"
                          placeholder="np. Klocki hamulcowe przednie do wymiany przy następnej wizycie"
                          class="{{ $pole }}" style="border-radius: 2px;">{{ old('zalecenia', $protokol?->zalecenia) }}</textarea>
                @error('zalecenia') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="data_nastepnej_wizyty" class="{{ $etykieta }}">ZALECANA KOLEJNA WIZYTA (OPCJONALNIE)</label>
                    <input type="date" id="data_nastepnej_wizyty" name="data_nastepnej_wizyty"
                           min="{{ now()->addDay()->toDateString() }}"
                           value="{{ old('data_nastepnej_wizyty', $protokol?->data_nastepnej_wizyty?->toDateString()) }}"
                           class="{{ $pole }}" style="border-radius: 2px;">
                    @error('data_nastepnej_wizyty') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="koszt_koncowy" class="{{ $etykieta }}">KOSZT KOŃCOWY (OPCJONALNIE)</label>
                    <input type="number" id="koszt_koncowy" name="koszt_koncowy" min="0" step="0.01"
                           value="{{ old('koszt_koncowy', $protokol?->koszt_koncowy) }}"
                           placeholder="{{ number_format($r->laczna_cena, 2, '.', '') }}"
                           class="{{ $pole }}" style="border-radius: 2px;">
                    <p class="text-xs text-zinc-500 mt-1.5">Puste pole = kwota wg cennika ({{ number_format($r->laczna_cena, 2, ',', ' ') }} zł).</p>
                    @error('koszt_koncowy') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">
                    {{ $nowy ? 'Zakończ wizytę i zapisz protokół' : 'Zapisz poprawki' }}
                </button>
                <a href="{{ route('pracownik.reservations.plan', ['data' => $r->termin_rozpoczecia->toDateString(), 'wizyta' => $r->id_rezerwacji]) }}"
                   class="flex-1 min-h-[44px] inline-flex items-center justify-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 text-sm font-semibold transition" style="border-radius: 2px;">
                    Wróć
                </a>
            </div>
        </form>

        {{-- Informacje o wizycie --}}
        <aside class="bg-[#1F2128] border border-zinc-800 divide-y divide-zinc-800" style="border-radius: 2px;">
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">WIZYTA</p>
                <p class="text-zinc-100">{{ $r->termin_rozpoczecia->translatedFormat('l, j F Y') }}</p>
                <p class="text-sm text-zinc-400">{{ $r->termin_rozpoczecia->format('H:i') }}–{{ $r->termin_zakonczenia->format('H:i') }}</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">KLIENT</p>
                <p class="text-zinc-100">{{ $r->klient->imie }} {{ $r->klient->nazwisko }}</p>
                @if ($r->klient->telefon) <p class="text-sm text-zinc-400">{{ $r->klient->telefon }}</p> @endif
            </div>
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">POJAZD</p>
                <p class="text-zinc-100">{{ $r->pojazd->marka }} {{ $r->pojazd->model }}</p>
                <p class="text-sm font-mono text-zinc-400">{{ $r->pojazd->numer_rejestracyjny }}</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-[11px] font-mono text-zinc-400 mb-1">USŁUGI</p>
                @foreach ($r->uslugi as $u)
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="text-zinc-300">{{ $u->nazwa }}</span>
                        <span class="text-zinc-400 font-mono whitespace-nowrap">{{ number_format($u->pivot->cena, 2, ',', ' ') }} zł</span>
                    </div>
                @endforeach
            </div>
            @if ($r->uwagi)
                <div class="px-5 py-4">
                    <p class="text-[11px] font-mono text-zinc-400 mb-1">UWAGI KLIENTA</p>
                    <p class="text-sm text-zinc-300 whitespace-pre-line">{{ $r->uwagi }}</p>
                </div>
            @endif
            @if ($poprzednia?->protokol->zalecenia)
                <div class="px-5 py-4">
                    <p class="text-[11px] font-mono text-amber-400 mb-1">ZALECENIA Z POPRZEDNIEJ WIZYTY</p>
                    <p class="text-sm text-zinc-300 whitespace-pre-line">{{ $poprzednia->protokol->zalecenia }}</p>
                    <p class="text-xs text-zinc-500 mt-1">{{ $poprzednia->termin_rozpoczecia->format('d.m.Y') }}</p>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
