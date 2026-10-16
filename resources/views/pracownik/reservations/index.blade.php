@extends(auth()->user()->rola === 'administrator' ? 'layouts.admin' : 'layouts.pracownik')
@section('title', 'Rezerwacje')

@section('content')
<div class="max-w-7xl">
    @include('pracownik.reservations._zakladki', ['aktywna' => 'lista', 'tytul' => 'Wszystkie rezerwacje'])

    @error('error')
        <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
            {{ $message }}
        </div>
    @enderror

    <div class="flex gap-2 mb-4 flex-wrap">
        @foreach (['dzisiaj' => 'Dzisiaj', 'oczekująca' => 'Oczekujące', 'zatwierdzona' => 'Zatwierdzone', 'zakończona' => 'Zakończone', 'wszystkie' => 'Wszystkie'] as $key => $label)
            <a href="{{ route('pracownik.reservations.index', ['status' => $key]) }}"
               class="text-sm font-medium px-3 py-1.5 transition {{ $status === $key ? 'bg-orange-600 text-white' : 'bg-[#1F2128] text-zinc-400 hover:text-zinc-200' }}"
               style="border-radius: 2px;">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-[#1F2128] border border-zinc-800 overflow-x-auto" style="border-radius: 2px;">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left">
                    <th class="px-4 py-3 font-mono text-xs text-zinc-500">KLIENT</th>
                    <th class="px-4 py-3 font-mono text-xs text-zinc-500">POJAZD</th>
                    <th class="px-4 py-3 font-mono text-xs text-zinc-500">USŁUGA</th>
                    <th class="px-4 py-3 font-mono text-xs text-zinc-500">TERMIN</th>
                    <th class="px-4 py-3 font-mono text-xs text-zinc-500">STATUS</th>
                    <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rezerwacje as $r)
                    <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                        <td class="px-4 py-3 text-zinc-200">{{ $r->klient->imie }} {{ $r->klient->nazwisko }}</td>
                        <td class="px-4 py-3 text-zinc-400">{{ $r->pojazd->marka }} {{ $r->pojazd->model }} ({{ $r->pojazd->numer_rejestracyjny }})</td>
                        <td class="px-4 py-3 text-zinc-400">
                            {{ $r->nazwy_uslug }}
                            @if ($r->uwagi)
                                <p class="text-xs text-zinc-600 mt-0.5">"{{ $r->uwagi }}"</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-400">
                            <a href="{{ route('pracownik.reservations.plan', ['data' => $r->termin_rozpoczecia->toDateString(), 'wizyta' => $r->id_rezerwacji]) }}"
                               class="whitespace-nowrap hover:text-orange-400" title="Pokaż na planie dnia">
                                {{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}–{{ $r->termin_zakonczenia->format('H:i') }}
                            </a>
                            <p class="text-xs mt-0.5">
                                @if ($r->mechanik)
                                    <span class="{{ $r->id_mechanika === auth()->id() ? 'text-orange-500 font-medium' : 'text-zinc-500' }}">
                                        {{ $r->mechanik->imie }} {{ $r->mechanik->nazwisko }}
                                    </span>
                                @else
                                    <span class="text-red-400">brak mechanika</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $kolory = [
                                    'oczekująca' => 'bg-yellow-900/30 text-yellow-400',
                                    'zatwierdzona' => 'bg-green-900/30 text-green-400',
                                    'odrzucona' => 'bg-red-900/30 text-red-400',
                                    'anulowana' => 'bg-zinc-800 text-zinc-500',
                                    'zakończona' => 'bg-blue-900/30 text-blue-400',
                                ];
                            @endphp
                            <span class="text-xs font-mono px-2 py-1 {{ $kolory[$r->status] }}" style="border-radius: 2px;">
                                {{ mb_strtoupper($r->status, 'UTF-8') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($r->status === 'oczekująca')
                                <div class="inline-flex gap-3">
                                    <form method="POST" action="{{ route('pracownik.reservations.approve', $r) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-500 hover:text-green-400">Zatwierdź</button>
                                    </form>
                                    <button type="button" class="text-red-500 hover:text-red-400"
                                            onclick="document.getElementById('reject-modal-{{ $r->id_rezerwacji }}').classList.remove('hidden')">
                                        Odrzuć
                                    </button>
                                    @if ($r->termin_rozpoczecia->isFuture())
                                        @if (auth()->user()->rola === 'administrator')
                                            <a href="{{ route('pracownik.reservations.przydzial', $r) }}" class="text-zinc-300 hover:text-white">Mechanik</a>
                                        @endif
                                        <a href="{{ route('pracownik.reservations.edit-termin', $r) }}" class="text-orange-500 hover:text-orange-400">Zmień termin</a>
                                    @endif
                                </div>

                                {{-- Okienko odrzucenia z powodem --}}
                                <div id="reject-modal-{{ $r->id_rezerwacji }}" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 px-4">
                                    <div class="bg-[#1F2128] border border-zinc-800 p-6 max-w-sm w-full" style="border-radius: 2px;">
                                        <p class="text-zinc-200 font-semibold mb-3 text-left">Odrzuć rezerwację</p>
                                        <form method="POST" action="{{ route('pracownik.reservations.reject', $r) }}" class="text-left">
                                            @csrf
                                            <label class="block text-xs font-mono text-zinc-400 mb-1.5">POWÓD (OPCJONALNIE – TRAFI DO MAILA DO KLIENTA)</label>
                                            <textarea name="powod" rows="3"
                                                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 mb-4 focus:outline-none focus:border-orange-600"
                                                style="border-radius: 2px;" placeholder="np. brak części, zły termin dla warsztatu"></textarea>
                                            <div class="flex gap-3">
                                                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 transition" style="border-radius: 2px;">
                                                    Odrzuć
                                                </button>
                                                <button type="button"
                                                        onclick="document.getElementById('reject-modal-{{ $r->id_rezerwacji }}').classList.add('hidden')"
                                                        class="flex-1 border border-zinc-600 text-zinc-300 font-semibold py-2.5 transition" style="border-radius: 2px;">
                                                    Anuluj
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @elseif ($r->status === 'zatwierdzona')
                                <div class="inline-flex gap-3">
                                    @if ($r->termin_rozpoczecia->isFuture() && auth()->user()->rola === 'administrator')
                                        <a href="{{ route('pracownik.reservations.przydzial', $r) }}" class="text-zinc-300 hover:text-white">Mechanik</a>
                                    @endif
                                    <a href="{{ route('pracownik.reservations.edit-termin', $r) }}" class="text-orange-500 hover:text-orange-400">Zmień termin</a>
                                    <form method="POST" action="{{ route('pracownik.reservations.complete', $r) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-500 hover:text-blue-400">Zakończ</button>
                                    </form>
                                </div>
                            @endif
                            @if (auth()->user()->rola === 'administrator')
                                <form method="POST" action="{{ route('admin.reservations.destroy', $r) }}" class="inline ml-3"
                                      onsubmit="return confirm('Trwale usunąć rezerwację {{ $r->klient->imie }} {{ $r->klient->nazwisko }} z {{ $r->termin_rozpoczecia->format('d.m.Y H:i') }}? Zniknie też z historii pojazdu.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400/80 hover:text-red-400">Usuń</button>
                                </form>
                            @endif                            
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-zinc-500">Brak rezerwacji w tej kategorii.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection