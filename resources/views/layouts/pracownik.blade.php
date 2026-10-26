<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel pracownika')</title>
    @vite('resources/css/app.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #15161A; }
        .nav-link { border-radius: 2px; color: #D4D4D8; }
        .nav-link svg { color: #A1A1AA; }
        .nav-link:not(.active):hover { background: #ffffff08; color: #FAFAFA; }
        .nav-link.active { background: #EA580C1F; color: #FAFAFA; }
        .nav-link.active svg { color: #F97316; }
    </style>
</head>
<body class="min-h-screen text-zinc-100 flex">

    @php
        $uzytkownik = auth()->user();
        $inicjaly = mb_strtoupper(mb_substr($uzytkownik->imie, 0, 1) . mb_substr($uzytkownik->nazwisko, 0, 1), 'UTF-8');

        // Licznik przy "Rezerwacjach": nadchodzące wizyty czekające na zatwierdzenie
        $liczbaOczekujacych = \App\Models\Rezerwacja::where('status', 'oczekująca')
            ->where('termin_rozpoczecia', '>', now())
            ->count();
    @endphp

    <aside class="w-64 bg-[#1F2128] border-r border-zinc-800 flex flex-col shrink-0 min-h-screen">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5">
            <div class="w-9 h-9 shrink-0 flex items-center justify-center bg-orange-600" style="border-radius: 2px;">
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-[15px] text-zinc-50">Warsztat</p>
                <p class="text-[11px] font-mono text-zinc-400">PANEL PRACOWNIKA</p>
            </div>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 px-3 pb-4 border-t border-zinc-800" aria-label="Menu pracownika">

            <p class="px-3 mt-4 mb-1.5 text-[10px] font-mono tracking-widest text-zinc-500">PRACA</p>

            <a href="{{ route('pracownik.reservations.plan') }}"
               class="nav-link flex items-center gap-3 min-h-[40px] px-3 text-sm font-medium {{ request()->routeIs('pracownik.reservations.*') ? 'active' : '' }}">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="16" rx="1"></rect><path d="M3 10h18M8 3v4M16 3v4"></path>
                </svg>
                Rezerwacje
                @if ($liczbaOczekujacych > 0)
                    <span class="ml-auto min-w-[20px] px-1.5 text-center text-[11px] font-semibold bg-yellow-900 text-yellow-200" style="border-radius: 10px;"
                          title="Oczekujące na zatwierdzenie">{{ $liczbaOczekujacych }}</span>
                @endif
            </a>

            <a href="{{ route('pracownik.clients.index') }}"
               class="nav-link flex items-center gap-3 min-h-[40px] px-3 text-sm font-medium {{ request()->routeIs('pracownik.clients.*') ? 'active' : '' }}">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="8" r="3.5"></circle><path d="M2.5 20c0-3.5 3-5.5 6.5-5.5s6.5 2 6.5 5.5"></path><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14.8c1.8.7 3 2.3 3 5.2"></path>
                </svg>
                Klienci i pojazdy
            </a>

            @if ($uzytkownik->rola === 'pracownik')
                <a href="{{ route('pracownik.schedule.index') }}"
                   class="nav-link flex items-center gap-3 min-h-[40px] px-3 text-sm font-medium {{ request()->routeIs('pracownik.schedule.*') ? 'active' : '' }}">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>
                    </svg>
                    Mój grafik
                </a>
            @endif

            <p class="px-3 mt-5 mb-1.5 text-[10px] font-mono tracking-widest text-zinc-500">KONTO</p>

            <a href="{{ route('profile.edit') }}"
               class="nav-link flex items-center gap-3 min-h-[40px] px-3 text-sm font-medium {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 4-6 8-6s8 2 8 6"></path>
                </svg>
                Mój profil
            </a>
        </nav>

        {{-- Użytkownik --}}
        <div class="flex items-center gap-3 px-4 py-3.5 border-t border-zinc-800">
            <a href="{{ route('profile.edit') }}" class="w-9 h-9 shrink-0 flex items-center justify-center bg-zinc-700 hover:bg-zinc-600 text-zinc-50 text-[13px] font-bold transition"
               style="border-radius: 50%;" title="Mój profil">
                {{ $inicjaly }}
            </a>
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-semibold text-zinc-100 truncate">{{ $uzytkownik->imie }} {{ $uzytkownik->nazwisko }}</p>
                <p class="text-xs text-zinc-400 truncate">{{ $uzytkownik->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" aria-label="Wyloguj się" title="Wyloguj się"
                    class="w-11 h-11 flex items-center justify-center border border-zinc-700 text-zinc-400 hover:text-red-400 hover:border-red-700 transition" style="border-radius: 2px;">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 4h4v16h-4"></path><path d="M10 8l-4 4 4 4"></path><path d="M6 12h10"></path>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 min-w-0 px-8 py-8">
        @if (session('status'))
            <div class="mb-6 bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 text-sm" style="border-radius: 2px;">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>