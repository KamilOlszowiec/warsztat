@extends('layouts.app')
@section('title', 'Odzyskiwanie hasła')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8">
            <p class="text-sm font-mono text-orange-500 mb-2">ODZYSKIWANIE HASŁA</p>
            <h1 class="text-2xl font-bold text-zinc-50">Zapomniałeś hasła?</h1>
            <p class="text-zinc-500 text-sm mt-2">Podaj email, wyślemy Ci link do zresetowania hasła.</p>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 text-sm" style="border-radius: 2px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="bg-[#1F2128] panel-accent p-6 space-y-4" style="border-radius: 2px;">
            @csrf

            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">EMAIL</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                @error('email') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
                Wyślij link resetujący
            </button>

            <p class="text-sm text-zinc-500 text-center pt-2">
                <a href="{{ route('login') }}" class="text-orange-500 hover:text-orange-400">← Powrót do logowania</a>
            </p>
        </form>
    </div>
</div>
@endsection