{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.admin')
@section('title', 'Użytkownicy')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm font-mono text-orange-500 mb-1">UŻYTKOWNICY</p>
        <h1 class="text-2xl font-bold text-zinc-50">Zarządzanie kontami</h1>
    </div>
    <a href="{{ route('admin.users.create') }}"
       class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 transition" style="border-radius: 2px;">
        + Dodaj konto
    </a>
</div>

@error('error')
    <div class="mb-4 bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">
        {{ $message }}
    </div>
@enderror

<div class="bg-[#1F2128] border border-zinc-800 overflow-hidden" style="border-radius: 2px;">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800 text-left">
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">IMIĘ I NAZWISKO</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">EMAIL</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">ROLA</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500">TELEFON</th>
                <th class="px-4 py-3 font-mono text-xs text-zinc-500 text-right">AKCJE</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="border-b border-zinc-800/50 hover:bg-white/[0.02]">
                    <td class="px-4 py-3 text-zinc-200">{{ $user->imie }} {{ $user->nazwisko }}</td>
                    <td class="px-4 py-3 text-zinc-400">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-mono px-2 py-1
                            @class([
                                'bg-orange-900/30 text-orange-400' => $user->rola === 'administrator',
                                'bg-blue-900/30 text-blue-400' => $user->rola === 'pracownik',
                                'bg-zinc-800 text-zinc-400' => $user->rola === 'klient',
                            ])" style="border-radius: 2px;">
                            {{ strtoupper($user->rola) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-zinc-400">{{ $user->telefon ?? '—' }}</td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-orange-500 hover:text-orange-400">Edytuj</a>
                        @if (auth()->id() !== $user->id_uzytkownika)
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                  onsubmit="return confirm('Na pewno usunąć to konto?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-400">Usuń</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection