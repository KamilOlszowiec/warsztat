<div {{ $attributes->merge(['class' => 'bg-[#1F2128] border border-zinc-800 p-4']) }} style="border-radius: 2px;">
    <div class="flex items-center justify-between mb-3">
        <button type="button" data-kal="poprzedni" aria-label="Poprzedni miesiąc"
                class="w-8 h-8 text-lg text-zinc-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">‹</button>
        <p data-kal="miesiac" class="text-sm font-medium text-zinc-200 capitalize"></p>
        <button type="button" data-kal="nastepny" aria-label="Następny miesiąc"
                class="w-8 h-8 text-lg text-zinc-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed">›</button>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-mono text-zinc-500 mb-1">
        @foreach (['PN', 'WT', 'ŚR', 'CZ', 'PT', 'SO', 'ND'] as $dzien)
            <span>{{ $dzien }}</span>
        @endforeach
    </div>
    <div data-kal="dni" class="grid grid-cols-7 gap-1"></div>
</div>
