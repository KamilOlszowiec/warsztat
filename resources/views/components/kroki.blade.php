{{--
    Pasek kroków kreatora. Obsługa: Rezerwacje.kroki() z resources/js/rezerwacje.js.
    Użycie: <x-kroki :nazwy="['Usługi', 'Termin', 'Pojazd', 'Potwierdzenie']" />
--}}
@props(['nazwy'])

<div {{ $attributes->merge(['class' => 'max-w-4xl mb-8']) }}>
    <p data-krok-licznik class="text-xs font-mono text-zinc-500 text-center mb-3">KROK 1 Z {{ count($nazwy) }}</p>
    <div class="flex items-center">
        @foreach (array_values($nazwy) as $i => $nazwa)
            @php $nr = $i + 1; @endphp
            <button type="button" data-krok-wskaznik="{{ $nr }}" class="flex flex-col items-center gap-1.5 shrink-0 disabled:cursor-not-allowed">
                <span class="krok-kolko w-9 h-9 flex items-center justify-center text-sm font-semibold border bg-[#1F2128] border-zinc-700 text-zinc-500" style="border-radius: 50%;">{{ $nr }}</span>
                <span class="krok-nazwa text-xs text-zinc-500">{{ $nazwa }}</span>
            </button>
            @if ($nr < count($nazwy))
                <div data-krok-linia="{{ $nr }}" class="flex-1 h-0.5 mx-2 mb-5 bg-zinc-800"></div>
            @endif
        @endforeach
    </div>
</div>
