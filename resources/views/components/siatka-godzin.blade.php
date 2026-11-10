@props(['komunikat' => 'Wybierz dzień w kalendarzu.', 'kolumny' => 'grid-cols-3 sm:grid-cols-4'])

<div {{ $attributes }}>
    <p class="text-xs font-mono text-zinc-400 mb-1">DOSTĘPNE GODZINY</p>
    <p data-sloty="opis" class="text-sm text-zinc-300 mb-3"></p>
    <p data-sloty="komunikat" class="text-zinc-500 text-sm">{{ $komunikat }}</p>
    <div data-sloty="lista" class="grid {{ $kolumny }} gap-2"></div>
    @error('godzina') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror
</div>
