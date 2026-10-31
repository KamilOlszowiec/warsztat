@extends(auth()->user()->czyAdministrator() ? 'layouts.admin' : 'layouts.pracownik')
@section('title', 'Zmień termin')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">ZMIANA TERMINU</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-2">
    {{ $rezerwacja->klient->imie }} {{ $rezerwacja->klient->nazwisko }} — {{ $rezerwacja->nazwy_uslug }}
</h1>
<p class="text-sm text-zinc-400 mb-6">
    Obecny termin:
    <span class="text-zinc-200 font-mono">
        {{ $rezerwacja->termin_rozpoczecia->format('d.m.Y H:i') }}–{{ $rezerwacja->termin_zakonczenia->format('H:i') }}
    </span>
    · Mechanik: <span class="text-zinc-200">{{ $rezerwacja->mechanik ? $rezerwacja->mechanik->imie . ' ' . $rezerwacja->mechanik->nazwisko : '—' }}</span>
    · Czas: <span class="text-zinc-200 font-mono">{{ $rezerwacja->laczny_czas }} min</span>
</p>
<p class="text-sm text-zinc-500 -mt-4 mb-6">
    @if (auth()->user()->czyAdministrator())
        Pokazane są wolne terminy wszystkich mechaników z wymaganymi specjalizacjami. Klient otrzyma e-mail z nowym terminem.
    @else
        Pokazane są tylko terminy, w których masz zmianę i jesteś wolny – wizyta pozostanie przypisana do Ciebie. Klient otrzyma e-mail z nowym terminem.
    @endif
</p>

<form id="formularz-terminu" method="POST" action="{{ route('pracownik.reservations.update-termin', $rezerwacja) }}" class="space-y-6 max-w-4xl">
    @csrf
    @method('PUT')
    <input type="hidden" name="data" id="pole-data" value="{{ old('data', $rezerwacja->termin_rozpoczecia->toDateString()) }}">

    @error('error') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

    <div class="grid grid-cols-1 md:grid-cols-[minmax(0,300px)_1fr] gap-6">
        <x-kalendarz id="kalendarz" />
        <div>
            <x-siatka-godzin id="godziny" />
            @error('data') <p class="text-sm text-red-500 mt-3">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex gap-3 max-w-md">
        <button type="submit" id="przycisk-zapisz" disabled
            class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-orange-600" style="border-radius: 2px;">
            Zapisz nowy termin
        </button>
        <a href="{{ route('pracownik.reservations.index') }}"
           class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
            Anuluj
        </a>
    </div>
</form>

@vite('resources/js/rezerwacje.js')
<script type="module">
const { format, kalendarz, siatkaGodzin } = window.Rezerwacje;

const poleData = document.getElementById('pole-data');
const przycisk = document.getElementById('przycisk-zapisz');
const obecnaData = @json($rezerwacja->termin_rozpoczecia->toDateString());
const obecnaGodzina = @json($rezerwacja->termin_rozpoczecia->format('H:i'));

const godziny = siatkaGodzin(document.getElementById('godziny'), {
    url: @json(route('pracownik.reservations.availability', $rezerwacja)),
    zaznacz: @json(old('godzina')),
    opis: () => (poleData.value ? format.data(poleData.value) + ' · czas wizyty: ' + format.czas(@json($rezerwacja->laczny_czas)) : ''),
    parametry: () => (poleData.value ? new URLSearchParams({ data: poleData.value }) : 'Wybierz dzień w kalendarzu.'),
    // Obecny termin wizyty oznaczony kropką
    dopisek: g => (poleData.value === obecnaData && g === obecnaGodzina ? ' •' : ''),
    poZmianie: () => { przycisk.disabled = !godziny.wybrana(); },
});

kalendarz(document.getElementById('kalendarz'), poleData, {
    dzisiaj: @json(now()->toDateString()),
    maksData: @json($maksData),
    dniZamkniete: @json($dniZamkniete),
    dniWolne: @json($dniWolne),
    poWyborze: () => godziny.pobierz(),
});

godziny.pobierz();
</script>
@endsection
