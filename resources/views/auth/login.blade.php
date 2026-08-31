{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')
@section('title', 'Logowanie')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8">
            <p class="text-sm font-mono text-orange-500 mb-2">LOGOWANIE</p>
            <h1 class="text-2xl font-bold text-zinc-50">Witaj z powrotem</h1>
        </div>

        <form method="POST" action="{{ route('login') }}" class="bg-[#1F2128] panel-accent p-6 space-y-4" style="border-radius: 2px;">
            @csrf

            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">EMAIL</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600"
                    style="border-radius: 2px;">
            </div>

            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">HASŁO</label>
                <input type="password" name="password"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600"
                    style="border-radius: 2px;">
            </div>

            @error('email')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror

            <button type="submit"
                class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition"
                style="border-radius: 2px;">
                Zaloguj się
            </button>

            <p class="text-sm text-zinc-500 text-center pt-2">
                Nie masz konta? <a href="{{ route('register') }}" class="text-orange-500 hover:text-orange-400">Zarejestruj się</a>
            </p>
        </form>
    </div>
</div>
@endsection