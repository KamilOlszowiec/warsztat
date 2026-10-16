@extends('layouts.auth')
@section('title', 'Potwierdź adres e-mail')

@section('content')
<div class="w-14 h-14 mb-5 flex items-center justify-center bg-orange-600/10" style="border-radius: 50%;">
    <svg aria-hidden="true" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FB923C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="5" width="18" height="14" rx="1"></rect><path d="M3 7l9 6 9-6"></path>
    </svg>
</div>

<h1 class="text-[26px] font-bold text-zinc-50 mb-2.5">Sprawdź skrzynkę</h1>
<p class="text-[15px] leading-relaxed text-zinc-300 mb-5">
    Wysłaliśmy link na <strong class="text-zinc-50">{{ auth()->user()->email }}</strong>.
    Kliknij go, żeby potwierdzić adres i zacząć rezerwować wizyty.
</p>

@if (session('status') == 'verification-link-sent')
    <div class="mb-5 flex gap-2.5 items-start bg-green-900/30 border border-green-700/60 px-3.5 py-3" style="border-radius: 2px;">
        <span class="w-2 h-2 shrink-0 mt-1.5 bg-green-400" style="border-radius: 50%;"></span>
        <p class="text-[13px] text-green-200">Nowy link został wysłany – poprzedni przestał działać.</p>
    </div>
@endif

<ul class="list-disc pl-[18px] mb-6 text-[13px] leading-relaxed text-zinc-400">
    <li>Nie widzisz maila? Zajrzyj do folderu spam lub oferty.</li>
    <li>Link jest ważny przez {{ config('auth.verification.expire', 60) }} minut.</li>
</ul>

<div class="flex flex-wrap gap-2.5">
    <form method="POST" action="{{ route('verification.send') }}" class="flex-1 min-w-[200px]">
        @csrf
        <button type="submit" class="w-full h-[46px] border border-zinc-700 hover:border-zinc-500 text-zinc-100 text-sm font-semibold transition" style="border-radius: 2px;">
            Wyślij link ponownie
        </button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="h-[46px] px-4 text-sm text-zinc-400 hover:text-zinc-200">Wyloguj się</button>
    </form>
</div>
@endsection