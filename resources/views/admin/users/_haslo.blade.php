<section class="bg-[#1F2128] border border-zinc-800" style="border-radius: 2px;">
    <h2 class="px-5 py-4 border-b border-zinc-800 text-[15px] font-semibold text-zinc-50">Hasło</h2>
    <div class="px-5 py-4 space-y-3">
        <form method="POST" action="{{ route('admin.users.password-link', $user) }}">
            @csrf
            <button type="submit" class="w-full min-h-[42px] border border-zinc-700 text-zinc-200 hover:text-white hover:border-zinc-500 text-[13px] font-medium transition" style="border-radius: 2px;">
                Wyślij link do zmiany hasła
            </button>
        </form>
        <p class="text-xs text-zinc-400">Zalecany sposób – nowe hasło zna wtedy tylko użytkownik.</p>

        <details class="group pt-1" @if ($errors->has('nowe_haslo')) open @endif>
            <summary class="list-none cursor-pointer text-[13px] text-zinc-300 hover:text-white [&::-webkit-details-marker]:hidden">
                <span class="inline-block transition-transform group-open:rotate-90">›</span>
                Ustaw hasło tymczasowe
            </summary>
            <form method="POST" action="{{ route('admin.users.password', $user) }}" class="mt-3 space-y-2.5">
                @csrf
                @method('PUT')
                <p class="text-xs text-zinc-400">Gdy link z maila nie dociera. Przekaż hasło użytkownikowi i poproś, żeby od razu zmienił je w profilu.</p>
                <input type="password" name="nowe_haslo" placeholder="Nowe hasło (min. 8 znaków)" autocomplete="new-password" required
                       class="w-full h-[40px] px-3 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm placeholder-zinc-500 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                <input type="password" name="nowe_haslo_confirmation" placeholder="Powtórz hasło" autocomplete="new-password" required
                       class="w-full h-[40px] px-3 bg-[#15161A] border border-zinc-700 text-zinc-100 text-sm placeholder-zinc-500 focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                @error('nowe_haslo') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                <button type="submit" class="w-full min-h-[40px] bg-zinc-700 hover:bg-zinc-600 text-zinc-50 text-[13px] font-medium transition" style="border-radius: 2px;">
                    Ustaw hasło
                </button>
            </form>
        </details>
    </div>
</section>