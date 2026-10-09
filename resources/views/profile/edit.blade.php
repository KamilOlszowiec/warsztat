@extends(auth()->user()->rola === 'klient' ? 'layouts.klient' : (auth()->user()->rola === 'administrator' ? 'layouts.admin' : 'layouts.klient'))
@section('title', 'Mój profil')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">USTAWIENIA</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Mój profil</h1>

<div class="space-y-6 max-w-lg">
    <form method="POST" action="{{ route('profile.update') }}" class="bg-[#1F2128] border border-zinc-800 p-6 space-y-4" style="border-radius: 2px;">
        @csrf
        @method('PUT')

        <p class="text-sm font-medium text-zinc-300 mb-2">Dane osobowe</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">IMIĘ</label>
                <input type="text" name="imie" value="{{ old('imie', $user->imie) }}"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                @error('imie') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-mono text-zinc-400 mb-1.5">NAZWISKO</label>
                <input type="text" name="nazwisko" value="{{ old('nazwisko', $user->nazwisko) }}"
                    class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                @error('nazwisko') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-mono text-zinc-400 mb-1.5">EMAIL</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            <p class="text-xs text-zinc-500 mt-1">Zmiana adresu wymaga ponownej weryfikacji.</p>
            @error('email') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-mono text-zinc-400 mb-1.5">TELEFON</label>
            <input type="text" name="telefon" value="{{ old('telefon', $user->telefon) }}"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        </div>

        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition" style="border-radius: 2px;">
            Zapisz zmiany
        </button>
    </form>

    <form method="POST" action="{{ route('profile.password') }}" class="bg-[#1F2128] border border-zinc-800 p-6 space-y-4" style="border-radius: 2px;">
        @csrf
        @method('PUT')

        <p class="text-sm font-medium text-zinc-300 mb-2">Zmiana hasła</p>

        <div>
            <label class="block text-xs font-mono text-zinc-400 mb-1.5">OBECNE HASŁO</label>
            <input type="password" name="current_password"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            @error('current_password') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-mono text-zinc-400 mb-1.5">NOWE HASŁO</label>
            <input type="password" name="password"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
            @error('password') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-mono text-zinc-400 mb-1.5">POWTÓRZ NOWE HASŁO</label>
            <input type="password" name="password_confirmation"
                class="w-full bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2.5 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
        </div>

        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 transition" style="border-radius: 2px;">
            Zmień hasło
        </button>
    </form>
</div>
@endsection