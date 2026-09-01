<?php
// app/Http/Controllers/Admin/GodzinyPracyController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GodzinyPracy;
use Illuminate\Http\Request;

class GodzinyPracyController extends Controller
{
    public function index()
    {
        $godziny = GodzinyPracy::orderBy('dzien_tygodnia')->get();
        return view('admin.schedule.index', compact('godziny'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'dni' => ['required', 'array'],
            'dni.*.id' => ['required', 'exists:godziny_pracy,id'],
            'dni.*.czy_czynne' => ['nullable', 'boolean'],
            'dni.*.godzina_otwarcia' => ['required_if:dni.*.czy_czynne,1', 'nullable', 'date_format:H:i'],
            'dni.*.godzina_zamkniecia' => ['required_if:dni.*.czy_czynne,1', 'nullable', 'date_format:H:i', 'after:dni.*.godzina_otwarcia'],
        ]);

        foreach ($data['dni'] as $dzien) {
            $czynne = isset($dzien['czy_czynne']) && $dzien['czy_czynne'];

            GodzinyPracy::where('id', $dzien['id'])->update([
                'czy_czynne' => $czynne,
                'godzina_otwarcia' => $czynne ? $dzien['godzina_otwarcia'] : null,
                'godzina_zamkniecia' => $czynne ? $dzien['godzina_zamkniecia'] : null,
            ]);
        }

        return redirect()->route('admin.schedule.index')->with('status', 'Godziny pracy zostały zaktualizowane.');
    }
}