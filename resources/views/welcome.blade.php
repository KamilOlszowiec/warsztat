@extends('layouts.app')
@section('title', 'Strona główna')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-4">
    <div class="text-center mb-12">
        <p class="text-sm font-mono text-orange-500 mb-3 tracking-wide">SYSTEM REZERWACJI</p>
        <h1 class="text-4xl md:text-5xl font-extrabold text-zinc-50 mb-4">
            Warsztat Samochodowy
        </h1>
        <p class="text-zinc-400 max-w-md mx-auto">
            Umów wizytę, sprawdź historię napraw i zarządzaj swoimi pojazdami — wszystko w jednym miejscu.
        </p>
    </div>

    <div class="bg-[#1F2128] panel-accent p-8 w-full max-w-sm space-y-3" style="border-radius: 2px;">
        <a href="{{ route('login') }}" class="block w-full text-center bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
            Zaloguj się
        </a>
        <a href="{{ route('register') }}" class="block w-full text-center border border-zinc-600 hover:border-zinc-400 text-zinc-200 font-semibold py-3 transition" style="border-radius: 2px;">
            Załóż konto
        </a>
    </div>
</div>
@endsection