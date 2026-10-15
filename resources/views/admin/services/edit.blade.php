@extends('layouts.admin')
@section('title', 'Edycja usługi')

@section('content')
<div class="max-w-2xl space-y-5">
    <a href="{{ route('admin.services.index', $usluga->czy_aktywna ? [] : ['widok' => 'ukryte']) }}" class="inline-block text-[13px] text-zinc-400 hover:text-zinc-200">← Usługi</a>

    @error('error')
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-4 py-3 text-sm" style="border-radius: 2px;">{{ $message }}</div>
    @enderror

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-mono text-orange-500 mb-1">OFERTA</p>
            <h1 class="text-2xl font-bold text-zinc-50">{{ $usluga->nazwa }}</h1>
        </div>
        <div class="flex gap-2.5">
            <a href="{{ route('admin.services.index') }}"
               class="inline-flex items-center min-h-[44px] px-4 border border-zinc-700 text-zinc-300 hover:text-white text-sm font-medium" style="border-radius: 2px;">Anuluj</a>
            <button type="submit" form="form-uslugi"
                    class="min-h-[44px] px-5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition" style="border-radius: 2px;">Zapisz zmiany</button>
        </div>
    </div>

    <form id="form-uslugi" method="POST" action="{{ route('admin.services.update', $usluga) }}"
          class="bg-[#1F2128] border border-zinc-800 p-6 space-y-4" style="border-radius: 2px;">
        @csrf
        @method('PUT')
        @include('admin.services._form', ['usluga' => $usluga])
    </form>

    <p class="text-xs text-zinc-400">Zmiana ceny lub czasu trwania dotyczy tylko nowych rezerwacji – umówione wizyty zachowują cenę i czas z chwili rezerwacji.</p>

    @if ($moznaUsunac)
        <form method="POST" action="{{ route('admin.services.destroy', $usluga) }}"
              onsubmit="return confirm('Usunąć usługę „{{ $usluga->nazwa }}”?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-[13px] text-red-400/80 hover:text-red-400">Usuń usługę</button>
        </form>
    @endif
</div>
@endsection