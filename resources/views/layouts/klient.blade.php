<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel klienta')</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        // Licznik przy "Rezerwacjach": nadchodzące wizyty oczekujące lub zatwierdzone
        $liczbaAktywnych = \App\Models\Rezerwacja::where('id_klienta', $uzytkownik->id_uzytkownika)
            ->whereIn('status', ['oczekująca', 'zatwierdzona'])
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
                <p class="text-[11px] font-mono text-zinc-400">PANEL KLIENTA</p>
            </div>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 px-3 pb-4 border-t border-zinc-800" aria-label="Menu klienta">

            <p class="px-3 mt-4 mb-1.5 text-[10px] font-mono tracking-widest text-zinc-500">WIZYTY</p>

            <a href="{{ route('klient.dashboard') }}"
               class="nav-link flex items-center gap-3 min-h-[40px] px-3 text-sm font-medium {{ request()->routeIs('klient.dashboard') ? 'active' : '' }}">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11l9-7 9 7"></path><path d="M5 10v10h14V10"></path>
                </svg>
                Panel główny
            </a>
            <a href="{{ route('klient.reservations.index') }}"
               class="nav-link flex items-center gap-3 min-h-[40px] px-3 text-sm font-medium {{ request()->routeIs('klient.reservations.*') ? 'active' : '' }}">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="16" rx="1"></rect><path d="M3 10h18M8 3v4M16 3v4"></path>
                </svg>
                Rezerwacje
                @if ($liczbaAktywnych > 0)
                    <span class="ml-auto min-w-[20px] px-1.5 text-center text-[11px] font-semibold bg-zinc-700 text-zinc-50" style="border-radius: 10px;"
                          title="Nadchodzące wizyty">{{ $liczbaAktywnych }}</span>
                @endif
            </a>

            <p class="px-3 mt-5 mb-1.5 text-[10px] font-mono tracking-widest text-zinc-500">GARAŻ</p>

            <a href="{{ route('klient.vehicles.index') }}"
               class="nav-link flex items-center gap-3 min-h-[40px] px-3 text-sm font-medium {{ request()->routeIs('klient.vehicles.*') ? 'active' : '' }}">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 17h14v-5l-2-5H7l-2 5v5z"></path><path d="M5 12h14"></path><circle cx="8" cy="17" r="2"></circle><circle cx="16" cy="17" r="2"></circle>
                </svg>
                Moje pojazdy
            </a>

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