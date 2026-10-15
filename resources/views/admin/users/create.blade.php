@extends('layouts.admin')
@section('title', 'Nowy pracownik')

@section('content')
<div class="max-w-3xl space-y-5">
    <a href="{{ route('admin.users.index') }}" class="inline-block text-[13px] text-zinc-400 hover:text-zinc-200">← Użytkownicy</a>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">ZESPÓŁ</p>
            <h1 class="text-2xl font-bold text-zinc-50">Nowy pracownik</h1>
        </div>
        <div class="flex gap-2.5">
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center min-h-[44px] px-4 border border-zinc-700 text-zinc-300 hover:text-white text-sm font-medium" style="border-radius: 2px;">Anuluj</a>
            <button type="submit" form="form-uzytkownika"
                    class="min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">Utwórz konto</button>
        </div>
    </div>

    <form id="form-uzytkownika" method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
        @csrf
        @include('admin.users._form')
    </form>
</div>
@endsection