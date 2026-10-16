<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Warsztat') — System rezerwacji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #15161A; }
    </style>
</head>
<body class="min-h-screen text-zinc-100 flex">

    {{-- Panel warsztatu (od szerokości laptopa) --}}
    <aside class="hidden lg:flex w-[46%] max-w-[620px] shrink-0 flex-col justify-between gap-10 px-14 py-12 bg-[#1F2128] border-r border-zinc-800">
        <a href="{{ route('login') }}" class="flex items-center gap-3 w-fit">
            <span class="w-10 h-10 flex items-center justify-center bg-orange-600" style="border-radius: 2px;">
                <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"></path>
                </svg>
            </span>
            <span class="text-lg font-bold text-zinc-50">Warsztat</span>
        </a>

        <div class="max-w-[420px]">
            <p class="text-xs font-mono text-orange-400 mb-2.5">REZERWACJE ONLINE</p>
            <h2 class="text-[32px] leading-tight font-bold text-zinc-50 mb-7">Umów wizytę w warsztacie bez dzwonienia</h2>
            <ul class="space-y-5">
                @foreach ([
                    ['Wolne terminy na bieżąco', 'Wybierasz usługi i godzinę, która Ci pasuje.', '<rect x="3" y="5" width="18" height="16" rx="1"></rect><path d="M3 10h18M8 3v4M16 3v4"></path>'],
                    ['Historia napraw każdego auta', 'Wszystkie wizyty i koszty w jednym miejscu.', '<path d="M5 17h14v-5l-2-5H7l-2 5v5z"></path><path d="M5 12h14"></path><circle cx="8" cy="17" r="2"></circle><circle cx="16" cy="17" r="2"></circle>'],
                    ['Powiadomienia e-mail', 'Wiesz od razu, gdy warsztat potwierdzi wizytę.', '<rect x="3" y="5" width="18" height="14" rx="1"></rect><path d="M3 7l9 6 9-6"></path>'],
                ] as [$tytul, $opis, $ikona])
                    <li class="flex gap-3.5 items-start">
                        <span class="w-9 h-9 shrink-0 flex items-center justify-center bg-orange-600/10" style="border-radius: 50%;">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FB923C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ikona !!}</svg>
                        </span>
                        <span>
                            <span class="block text-[15px] font-semibold text-zinc-100">{{ $tytul }}</span>
                            <span class="block text-sm text-zinc-400 mt-0.5">{{ $opis }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="text-xs text-zinc-500">© {{ date('Y') }} Warsztat · system rezerwacji wizyt</p>
    </aside>

    {{-- Formularz --}}
    <main class="flex-1 min-w-0 flex items-center justify-center px-5 py-12">
        <div class="w-full max-w-[400px]">
            {{-- Logo na telefonie --}}
            <a href="{{ route('login') }}" class="lg:hidden flex items-center gap-2.5 mb-10 w-fit">
                <span class="w-9 h-9 flex items-center justify-center bg-orange-600" style="border-radius: 2px;">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"></path>
                    </svg>
                </span>
                <span class="text-base font-bold text-zinc-50">Warsztat</span>
            </a>

            @yield('content')
        </div>
    </main>
</body>
</html>