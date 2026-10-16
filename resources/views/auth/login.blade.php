@extends('layouts.auth')
@section('title', 'Logowanie')

@section('content')
@php $pole = 'w-full h-[46px] px-3.5 bg-[#1F2128] border border-zinc-700 text-zinc-50 text-[15px] placeholder-zinc-500 focus:outline-none focus:border-orange-600'; @endphp

<h1 class="text-[26px] font-bold text-zinc-50 mb-1.5">Zaloguj się</h1>
<p class="text-sm text-zinc-400 mb-7">Witaj ponownie! Wpisz dane swojego konta.</p>

@if (session('status'))
    <div class="mb-5 bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-[18px]">
    @csrf

    <label class="block">
        <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">E-MAIL</span>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
               class="{{ $pole }} {{ $errors->has('email') ? 'border-red-600' : '' }}" style="border-radius: 2px;">
    </label>

    <div>
        <div class="flex justify-between items-baseline mb-1.5">
            <label for="haslo" class="text-[11px] font-mono text-zinc-400">HASŁO</label>
            <a href="{{ route('password.request') }}" class="text-xs text-orange-500 hover:text-orange-400">Nie pamiętasz hasła?</a>
        </div>
        <div class="relative">
            <input id="haslo" type="password" name="password" required autocomplete="current-password"
                   class="{{ $pole }} pr-20" style="border-radius: 2px;">
            <button type="button" id="pokaz-haslo" aria-controls="haslo"
                    class="absolute right-1.5 top-1.5 h-[34px] px-2.5 text-xs text-zinc-400 hover:text-zinc-100">Pokaż</button>
        </div>
    </div>

    @error('email')
        <p class="text-sm text-red-400">{{ $message }}</p>
    @enderror

    <label class="flex items-center gap-2.5 text-sm text-zinc-300 cursor-pointer">
        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="w-4 h-4 accent-orange-600">
        Zapamiętaj mnie na tym urządzeniu
    </label>

    <button type="submit" class="w-full h-12 mt-1 bg-orange-600 hover:bg-orange-700 text-white text-[15px] font-semibold transition" style="border-radius: 2px;">
        Zaloguj się
    </button>
</form>

<div class="flex items-center gap-3 my-7">
    <span class="flex-1 h-px bg-zinc-800"></span>
    <span class="text-xs text-zinc-500">pierwszy raz tutaj?</span>
    <span class="flex-1 h-px bg-zinc-800"></span>
</div>
<a href="{{ route('register') }}"
   class="flex items-center justify-center h-[46px] border border-zinc-700 hover:border-zinc-500 text-zinc-100 text-sm font-semibold transition" style="border-radius: 2px;">
    Załóż konto klienta
</a>

<script>
    document.getElementById('pokaz-haslo').addEventListener('click', function () {
        const pole = document.getElementById('haslo');
        const ukryte = pole.type === 'password';
        pole.type = ukryte ? 'text' : 'password';
        this.textContent = ukryte ? 'Ukryj' : 'Pokaż';
    });
</script>
@endsection