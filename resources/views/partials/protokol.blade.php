{{--
    Protokół z zakończonej wizyty w historii pojazdu.
    Parametry: $r – rezerwacja (status „zakończona”)
--}}
@if ($r->maProtokol())
    <div class="mt-2 px-3 py-2.5 bg-[#15161A] border border-zinc-800 text-xs space-y-1.5" style="border-radius: 2px;">
        <div class="flex flex-wrap gap-x-4 gap-y-1 font-mono text-zinc-400">
            @if ($r->protokol->przebieg !== null)
                <span>PRZEBIEG: <span class="text-zinc-200">{{ number_format($r->protokol->przebieg, 0, ',', ' ') }} km</span></span>
            @endif
            <span>KOSZT: <span class="text-zinc-200">{{ number_format($r->kwota_koncowa, 2, ',', ' ') }} zł</span></span>
        </div>
        <p class="text-zinc-300 whitespace-pre-line"><span class="text-zinc-500">Wykonane prace:</span> {{ $r->protokol->opis_prac }}</p>
        @if ($r->protokol->zalecenia)
            <p class="text-amber-300/90 whitespace-pre-line"><span class="text-zinc-500">Zalecenia:</span> {{ $r->protokol->zalecenia }}</p>
        @endif
        @if ($r->protokol->data_nastepnej_wizyty)
            <p class="text-zinc-400">Zalecana kolejna wizyta: <span class="text-zinc-200">{{ $r->protokol->data_nastepnej_wizyty->format('d.m.Y') }}</span></p>
        @endif
    </div>
@endif
