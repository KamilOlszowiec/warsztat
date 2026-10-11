<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły rezerwacji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #15161A; }
    </style>
</head>
<body class="min-h-screen text-zinc-100 flex items-center justify-center px-4 py-12">

    <div class="w-full max-w-lg">
        <div class="text-center mb-6">
            <p class="text-sm font-mono text-orange-500 mb-2">SZCZEGÓŁY REZERWACJI</p>
            <h1 class="text-2xl font-bold text-zinc-50">Warsztat Samochodowy</h1>
        </div>

        <div class="bg-[#1F2128] border-l-4 border-orange-600 p-6 md:p-8" style="border-radius: 2px;">
            @php
                $kolory = [
                    'oczekująca' => 'bg-yellow-900/30 text-yellow-400',
                    'zatwierdzona' => 'bg-green-900/30 text-green-400',
                    'odrzucona' => 'bg-red-900/30 text-red-400',
                    'anulowana' => 'bg-zinc-800 text-zinc-500',
                    'zakończona' => 'bg-blue-900/30 text-blue-400',
                ];
            @endphp

            <div class="flex items-center justify-between mb-6">
                <p class="text-zinc-400 text-sm">Rezerwacja #{{ $rezerwacja->id_rezerwacji }}</p>
                <span class="text-xs font-mono px-2 py-1 {{ $kolory[$rezerwacja->status] }}" style="border-radius: 2px;">
                    {{ mb_strtoupper($rezerwacja->status, 'UTF-8') }}
                </span>
            </div>

            <dl class="space-y-4">
                <div>
                    <dt class="text-xs font-mono text-zinc-500 mb-1">KLIENT</dt>
                    <dd class="text-zinc-200">{{ $rezerwacja->klient->imie }} {{ $rezerwacja->klient->nazwisko }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-mono text-zinc-500 mb-1">USŁUGA</dt>
                    <dd class="text-zinc-200">{{ $rezerwacja->nazwy_uslug }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-mono text-zinc-500 mb-1">POJAZD</dt>
                    <dd class="text-zinc-200">{{ $rezerwacja->pojazd->marka }} {{ $rezerwacja->pojazd->model }} ({{ $rezerwacja->pojazd->numer_rejestracyjny }})</dd>
                </div>
                <div>
                    <dt class="text-xs font-mono text-zinc-500 mb-1">TERMIN</dt>
                    <dd class="text-zinc-200">{{ $rezerwacja->termin_rozpoczecia->translatedFormat('d F Y, H:i') }}</dd>
                </div>
                @if ($rezerwacja->status === 'odrzucona' && $rezerwacja->powod_odrzucenia)
                    <div>
                        <dt class="text-xs font-mono text-zinc-500 mb-1">POWÓD ODRZUCENIA</dt>
                        <dd class="text-zinc-400">{{ $rezerwacja->powod_odrzucenia }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-8 pt-6 border-t border-zinc-800 text-center">
                <a href="{{ route('login') }}" class="text-orange-500 hover:text-orange-400 text-sm font-medium">
                    Zaloguj się, aby zobaczyć wszystkie rezerwacje →
                </a>
            </div>
        </div>
    </div>

</body>
</html>