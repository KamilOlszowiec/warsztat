{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')
@section('title', 'Panel administratora')

@section('content')
<div class="min-h-screen px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-sm font-mono text-orange-500 mb-1">PANEL ADMINISTRATORA</p>
                <h1 class="text-2xl font-bold text-zinc-50">Witaj, {{ auth()->user()->imie }}</h1>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="border border-zinc-600 hover:border-red-500 hover:text-red-400 text-zinc-300 text-sm font-medium px-4 py-2 transition"
                    style="border-radius: 2px;">
                    Wyloguj się
                </button>
            </form>
        </div>

        <div class="bg-[#1F2128] panel-accent p-6" style="border-radius: 2px;">
            <p class="text-zinc-400">Tu pojawi się zarządzanie użytkownikami, usługami i harmonogramem.</p>
        </div>
    </div>
</div>
@endsection