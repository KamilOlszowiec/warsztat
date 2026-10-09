@extends('layouts.admin')
@section('title', 'Godziny pracy')

@section('content')
<p class="text-sm font-mono text-orange-500 mb-1">HARMONOGRAM</p>
<h1 class="text-2xl font-bold text-zinc-50 mb-6">Godziny pracy warsztatu</h1>

<form method="POST" action="{{ route('admin.schedule.update') }}" class="bg-[#1F2128] border border-zinc-800 p-6 max-w-2xl" style="border-radius: 2px;">
    @csrf
    @method('PUT')

    <div class="space-y-4">
        @foreach ($godziny as $i => $dzien)
            <div class="flex items-center gap-4 pb-4 {{ !$loop->last ? 'border-b border-zinc-800' : '' }}">
                <input type="hidden" name="dni[{{ $i }}][id]" value="{{ $dzien->id }}">

                <div class="w-32 shrink-0">
                    <span class="text-sm font-medium text-zinc-200">{{ \App\Models\GodzinyPracy::DNI[$dzien->dzien_tygodnia] }}</span>
                </div>

                <label class="flex items-center gap-2 text-sm text-zinc-400 shrink-0">
                    <input type="checkbox" name="dni[{{ $i }}][czy_czynne]" value="1"
                        {{ old("dni.$i.czy_czynne", $dzien->czy_czynne) ? 'checked' : '' }}
                        class="w-4 h-4 accent-orange-600 day-toggle" data-index="{{ $i }}">
                    Czynne
                </label>

                <div class="flex items-center gap-2 flex-1" id="hours-{{ $i }}">
                    <input type="time" name="dni[{{ $i }}][godzina_otwarcia]"
                        value="{{ old("dni.$i.godzina_otwarcia", $dzien->godzina_otwarcia ? \Carbon\Carbon::parse($dzien->godzina_otwarcia)->format('H:i') : '08:00') }}"
                        class="bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2 text-sm focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                    <span class="text-zinc-500">—</span>
                    <input type="time" name="dni[{{ $i }}][godzina_zamkniecia]"
                        value="{{ old("dni.$i.godzina_zamkniecia", $dzien->godzina_zamkniecia ? \Carbon\Carbon::parse($dzien->godzina_zamkniecia)->format('H:i') : '16:00') }}"
                        class="bg-[#15161A] border border-zinc-700 text-zinc-100 px-3 py-2 text-sm focus:outline-none focus:border-orange-600" style="border-radius: 2px;">
                </div>
            </div>
        @endforeach
    </div>

    @error('dni') <p class="text-sm text-red-500 mt-4">{{ $message }}</p> @enderror

    <button type="submit" class="mt-6 bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-3 transition" style="border-radius: 2px;">
        Zapisz godziny pracy
    </button>
</form>

<script>
    document.querySelectorAll('.day-toggle').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const hoursDiv = document.getElementById('hours-' + this.dataset.index);
            hoursDiv.style.opacity = this.checked ? '1' : '0.3';
            hoursDiv.querySelectorAll('input').forEach(function (input) {
                input.disabled = !checkbox.checked;
            });
        });
        checkbox.dispatchEvent(new Event('change'));
    });
</script>
@endsection