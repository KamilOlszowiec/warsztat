{{-- resources/views/auth/verify-email.blade.php --}}
@extends('layouts.app')
@section('title', 'Weryfikacja email')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="bg-[#1F2128] panel-accent p-6 text-center" style="border-radius: 2px;">
            <p class="text-sm font-mono text-orange-500 mb-2">WERYFIKACJA</p>
            <h1 class="text-xl font-bold text-zinc-50 mb-3">Potwierdź adres email</h1>
            <p class="text-zinc-400 text-sm mb-6">
                Wysłaliśmy link weryfikacyjny na Twój adres email. Kliknij go, żeby aktywować konto.
            </p>

            @if (session('status') == 'verification-link-sent')
                <p class="text-sm text-green-400 mb-4">Nowy link weryfikacyjny został wysłany.</p>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                @csrf
                <button type="submit"
                    class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition"
                    style="border-radius: 2px;">
                    Wyślij ponownie
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-medium py-3 transition"
                    style="border-radius: 2px;">
                    Wyloguj
                </button>
            </form>
        </div>
    </div>
</div>
@endsection