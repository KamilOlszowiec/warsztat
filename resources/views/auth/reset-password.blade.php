@extends('layouts.auth')
@section('title', 'Nowe hasło')

@section('content')
<h1 class="text-[26px] font-bold text-zinc-50 mb-1.5">Ustaw nowe hasło</h1>
<p class="text-sm text-zinc-400 mb-7">Wybierz hasło, którego nie używasz w innych serwisach.</p>

<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <label class="block">
        <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">E-MAIL</span>
        <input type="email" name="email" value="{{ old('email', $email) }}" readonly
               class="w-full h-[46px] px-3.5 bg-[#18191D] border border-zinc-800 text-zinc-400 text-[15px] cursor-not-allowed" style="border-radius: 2px;">
        @error('email') <span class="block text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
    </label>

    <div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2.5">
            <label class="block">
                <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">NOWE HASŁO</span>
                <input id="haslo" type="password" name="password" required autofocus autocomplete="new-password"
                       class="w-full h-[46px] px-3.5 bg-[#1F2128] border {{ $errors->has('password') ? 'border-red-600' : 'border-zinc-700' }} text-zinc-50 text-[15px] focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            </label>
            <label class="block">
                <span class="block mb-1.5 text-[11px] font-mono text-zinc-400">POWTÓRZ HASŁO</span>
                <input id="haslo-powtorz" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full h-[46px] px-3.5 bg-[#1F2128] border border-zinc-700 text-zinc-50 text-[15px] focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            </label>
        </div>
        @include('auth._sila-hasla', ['pole' => 'haslo', 'powtorzenie' => 'haslo-powtorz'])
        @error('password') <p class="text-xs text-red-400 mt-1.5">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="w-full h-12 mt-1 bg-orange-600 hover:bg-orange-700 text-white text-[15px] font-semibold transition" style="border-radius: 2px;">
        Zmień hasło
    </button>
</form>
@endsection