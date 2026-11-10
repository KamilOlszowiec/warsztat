@props(['status', 'rozmiar' => 'maly'])

@php
    $s = $status instanceof \App\Enums\StatusRezerwacji ? $status : \App\Enums\StatusRezerwacji::tryFrom((string) $status);
    $wymiary = [
        'maly'   => 'text-[10px] px-1.5 py-0.5',
        'sredni' => 'text-[11px] px-2 py-0.5',
        'duzy'   => 'text-xs px-2 py-1',
    ][$rozmiar] ?? 'text-[10px] px-1.5 py-0.5';
@endphp

<span {{ $attributes->merge(['class' => 'font-mono ' . $wymiary . ' ' . ($s?->klasy() ?? 'bg-zinc-800 text-zinc-400')]) }} style="border-radius: 2px;">{{ mb_strtoupper($s?->value ?? (string) $status, 'UTF-8') }}</span>
