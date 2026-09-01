{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.admin')
@section('title', 'Dodaj konto')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">NOWE KONTO</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Dodaj użytkownika</h1>

<form method="POST" action="{{ route('admin.users.store') }}" class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg space-y-4" style="border-radius: 2px;">
    @csrf
    @include('admin.users._form')

    <div class="flex gap-3">
        <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
            Utwórz konto
        </button>
        <a href="{{ route('admin.users.index') }}"
           class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
            Anuluj
        </a>
    </div>
</form>
@endsection