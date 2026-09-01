{{-- resources/views/admin/users/edit.blade.php --}}
@extends('layouts.admin')
@section('title', 'Edytuj konto')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">EDYCJA</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">{{ $user->imie }} {{ $user->nazwisko }}</h1>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-[#1F2128] border border-zinc-800 p-6 max-w-lg space-y-4" style="border-radius: 2px;">
    @csrf
    @method('PUT')
    @include('admin.users._form', ['user' => $user])

    <div class="flex gap-3">
        <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 transition" style="border-radius: 2px;">
            Zapisz zmiany
        </button>
        <a href="{{ route('admin.users.index') }}"
           class="flex-1 text-center border border-zinc-600 hover:border-zinc-400 text-zinc-300 font-semibold py-3 transition" style="border-radius: 2px;">
            Anuluj
        </a>
    </div>
</form>
@endsection