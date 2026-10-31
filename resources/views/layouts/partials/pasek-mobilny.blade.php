{{-- Górny pasek na telefonie (logo i przycisk menu) oraz przyciemnienie tła pod otwartym menu --}}
<header class="lg:hidden sticky top-0 z-30 flex items-center justify-between h-14 px-4 bg-[#1F2128] border-b border-zinc-800">
    <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 flex items-center justify-center bg-orange-600" style="border-radius: 2px;">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"></path>
            </svg>
        </div>
        <p class="font-bold text-[15px] text-zinc-50">Warsztat</p>
    </div>
    <button type="button" id="menu-otworz" aria-controls="menu-boczne" aria-expanded="false" aria-label="Otwórz menu"
            class="w-11 h-11 flex items-center justify-center text-zinc-200 border border-zinc-700" style="border-radius: 2px;">
        <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
</header>

{{-- Przyciemnienie tła pod otwartym menu --}}
<div id="menu-tlo" class="hidden lg:hidden fixed inset-0 z-40 bg-black/60"></div>
