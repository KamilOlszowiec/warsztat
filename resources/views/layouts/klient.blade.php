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
        .nav-link { border-radius: 2px; }
        .nav-link.active { background: #E8590C1A; border-left: 3px solid #E8590C; color: #F4F4F5; }
        .nav-link:not(.active):hover { background: #ffffff08; }
    </style>
</head>
<body class="min-h-screen text-zinc-100 flex">

    <aside class="w-64 bg-[#1F2128] border-r border-zinc-800 flex flex-col shrink-0 min-h-screen">
        <div class="px-5 py-6 border-b border-zinc-800">
            <p class="text-xs font-mono text-orange-500 mb-1">PANEL</p>
            <h1 class="font-bold text-lg text-zinc-50">Klient</h1>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1">
            <a href="{{ route('klient.dashboard') }}"
               class="nav-link flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-zinc-300 {{ request()->routeIs('klient.dashboard') ? 'active' : '' }}">
                Panel główny
            </a>
            <a href="{{ route('klient.vehicles.index') }}"
               class="nav-link flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-zinc-300 {{ request()->routeIs('klient.vehicles.*') ? 'active' : '' }}">
                Moje pojazdy
            </a>
            <a href="{{ route('klient.reservations.index') }}"
                class="nav-link flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-zinc-300 {{ request()->routeIs('klient.reservations.*') ? 'active' : '' }}">
                    Rezerwacje
                </a>
                <a href="{{ route('profile.edit') }}"
                class="nav-link flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-zinc-300 {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    Mój profil
                </a>
        </nav>

        <div class="px-3 py-4 border-t border-zinc-800">
            <div class="px-3 py-2 mb-2">
                <p class="text-sm font-medium text-zinc-200">{{ auth()->user()->imie }} {{ auth()->user()->nazwisko }}</p>
                <p class="text-xs text-zinc-500 font-mono">KLIENT</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="nav-link w-full text-left flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-zinc-400 hover:text-red-400">
                    Wyloguj się
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 px-8 py-8">
        @if (session('status'))
            <div class="mb-6 bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 text-sm" style="border-radius: 2px;">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>