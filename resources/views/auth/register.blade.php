@extends('layouts.auth')
@section('title', 'Rejestracja')

@section('content')
@php $pole = 'w-full h-[46px] px-3.5 bg-[#1F2128] border text-zinc-50 text-[15px] placeholder-zinc-500 focus:outline-none focus:border-orange-600'; @endphp

<h1 class="text-[26px] font-bold text-zinc-50 mb-1.5">Załóż konto</h1>
<p class="text-sm text-zinc-400 mb-7">Zajmie to minutę. Potem od razu umówisz pierwszą wizytę.</p>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">IMIĘ</span>
            <input type="text" name="imie" value="{{ old('imie') }}" required autofocus autocomplete="given-name" placeholder="Jan"
                   class="{{ $pole }} {{ $errors->has('imie') ? 'border-red-600' : 'border-zinc-700' }}" style="border-radius: 2px;">
            @error('imie') <span class="block text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">NAZWISKO</span>
            <input type="text" name="nazwisko" value="{{ old('nazwisko') }}" required autocomplete="family-name" placeholder="Kowalski"
                   class="{{ $pole }} {{ $errors->has('nazwisko') ? 'border-red-600' : 'border-zinc-700' }}" style="border-radius: 2px;">
            @error('nazwisko') <span class="block text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
        </label>
    </div>

    <label class="block">
        <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">E-MAIL</span>
        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="jan@example.com"
               class="{{ $pole }} {{ $errors->has('email') ? 'border-red-600' : 'border-zinc-700' }}" style="border-radius: 2px;">
        @error('email') <span class="block text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">TELEFON (OPCJONALNIE)</span>
        <input type="tel" name="telefon" value="{{ old('telefon') }}" autocomplete="tel" placeholder="500 100 200"
               class="{{ $pole }} {{ $errors->has('telefon') ? 'border-red-600' : 'border-zinc-700' }}" style="border-radius: 2px;">
        <span class="block text-xs text-zinc-500 mt-1">Warsztat zadzwoni tylko w sprawie Twojej wizyty.</span>
        @error('telefon') <span class="block text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
    </label>

    <div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2.5">
            <label class="block">
                <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">HASŁO</span>
                <input id="haslo" type="password" name="password" required autocomplete="new-password"
                       class="{{ $pole }} {{ $errors->has('password') ? 'border-red-600' : 'border-zinc-700' }}" style="border-radius: 2px;">
            </label>
            <label class="block">
                <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">POWTÓRZ HASŁO</span>
                <input id="haslo-powtorz" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="{{ $pole }} border-zinc-700" style="border-radius: 2px;">
            </label>
        </div>
        @include('auth._sila-hasla', ['pole' => 'haslo', 'powtorzenie' => 'haslo-powtorz'])
        @error('password') <p class="text-xs text-red-400 mt-1.5">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="w-full h-12 mt-1 bg-orange-600 hover:bg-orange-700 text-white text-[15px] font-semibold transition" style="border-radius: 2px;">
        Załóż konto
    </button>
    <p class="text-xs leading-relaxed text-zinc-500">Po rejestracji wyślemy link do potwierdzenia adresu e-mail. Rezerwować możesz po jego kliknięciu.</p>
</form>

<p class="mt-7 pt-5 border-t border-zinc-800 text-sm text-zinc-400 text-center">
    Masz już konto? <a href="{{ route('login') }}" class="font-semibold text-orange-500 hover:text-orange-400">Zaloguj się</a>
</p>
@endsection