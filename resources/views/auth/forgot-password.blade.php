@extends('layouts.auth')
@section('title', 'Odzyskiwanie hasła')

@section('content')
<a href="{{ route('login') }}" class="inline-block text-[13px] text-zinc-400 hover:text-zinc-200 mb-6">← Powrót do logowania</a>

<h1 class="text-[26px] font-bold text-zinc-50 mb-1.5">Nie pamiętasz hasła?</h1>
<p class="text-sm text-zinc-400 mb-7">Podaj adres e-mail konta. Wyślemy link, przez który ustawisz nowe hasło.</p>

@if (session('status'))
    <div class="mb-5 flex gap-2.5 items-start bg-green-900/30 border border-green-700 px-4 py-3" style="border-radius: 2px;">
        <span class="w-2 h-2 shrink-0 mt-1.5 bg-green-400" style="border-radius: 50%;"></span>
        <p class="text-sm text-green-300">{{ session('status') }} Sprawdź też folder spam.</p>
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-[18px]">
    @csrf
    <label class="block">
        <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">E-MAIL</span>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
               class="w-full h-[46px] px-3.5 bg-[#1F2128] border {{ $errors->has('email') ? 'border-red-600' : 'border-zinc-700' }} text-zinc-50 text-[15px] focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        @error('email') <span class="block text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
    </label>

    <button type="submit" class="w-full h-12 bg-orange-600 hover:bg-orange-700 text-white text-[15px] font-semibold transition" style="border-radius: 2px;">
        Wyślij link
    </button>
</form>
@endsection