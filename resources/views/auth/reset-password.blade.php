@extends('layouts.app')
@section('title', 'Reset hasła')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8">
            <p class="text-sm font-mono text-orange-500 mb-2">NOWE HASŁO</p>
            <h1 class="text-2xl font-bold text-zinc-50">Ustaw nowe hasło</h1>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="bg-[#1F2128] panel-accent p-6 space-y-4" style="border-radius: 2px;">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">EMAIL</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" readonly
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-500 px-3 py-2.5 cursor-not-allowed" style="border-radius: 2px;">
                @error('email') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">NOWE HASŁO</label>
                <input type="password" name="password"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                @error('password') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">POWTÓRZ HASŁO</label>
                <input type="password" name="password_confirmation"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            </div>

            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
                Zmień hasło
            </button>
        </form>
    </div>
</div>
@endsection