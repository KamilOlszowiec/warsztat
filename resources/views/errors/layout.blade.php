{{-- Wspólny układ stron błędów. Celowo bez @vite i bez bazy danych,
     żeby strona wyświetliła się nawet przy awarii aplikacji. --}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('kod') – @yield('tytul') · Warsztat</title>
    <style>
        html, body { margin: 0; min-height: 100%; background: #15161A; color: #F4F4F5;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        .strona { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; }
        .karta { max-width: 440px; width: 100%; background: #1F2128; border: 1px solid #27272A; border-radius: 2px; padding: 36px 32px; }
        .logo { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 28px; color: #FAFAFA; font-weight: 700; text-decoration: none; }
        .logo span { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: #EA580C; border-radius: 2px; }
        .kod { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; letter-spacing: 1px; color: #F97316; margin: 0 0 8px; }
        h1 { font-size: 24px; line-height: 1.25; margin: 0 0 12px; }
        p.opis { font-size: 15px; line-height: 1.6; color: #A1A1AA; margin: 0 0 28px; }
        .przycisk { display: inline-flex; align-items: center; min-height: 44px; padding: 0 20px; background: #EA580C; color: #FFFFFF;
            font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 2px; }
        .przycisk:hover { background: #C2410C; }
        .link { margin-left: 16px; color: #D4D4D8; font-size: 14px; text-decoration: none; }
        .link:hover { color: #FFFFFF; }
    </style>
</head>
<body>
    <main class="strona">
        <div class="karta">
            <a href="{{ url('/') }}" class="logo">
                <span>
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"></path>
                    </svg>
                </span>
                Warsztat
            </a>
            <p class="kod">BŁĄD @yield('kod')</p>
            <h1>@yield('tytul')</h1>
            <p class="opis">@yield('opis')</p>
            <a href="{{ url('/') }}" class="przycisk">Strona główna</a>
            <a href="javascript:history.back()" class="link">← Wróć</a>
        </div>
    </main>
</body>
</html>
