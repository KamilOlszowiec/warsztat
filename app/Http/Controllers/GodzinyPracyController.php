<?php
// app/Http/Controllers/GodzinyPracyController.php
namespace App\Http\Controllers;

use App\Models\GodzinyPracy;
use Illuminate\Http\Request;

class GodzinyPracyController extends Controller
{
    public function index()
    {
        $godziny = GodzinyPracy::orderBy('dzien_tygodnia')->get();
        $layout = auth()->user()->rola === 'administrator' ? 'layouts.admin' : 'layouts.pracownik';

        return view('schedule.index', compact('godziny', 'layout'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'dni' => ['required', 'array'],
            'dni.*.id' => ['required', 'exists:godziny_pracy,id'],
            'dni.*.czy_czynne' => ['nullable', 'boolean'],
            'dni.*.godzina_otwarcia' => ['required_if:dni.*.czy_czynne,1', 'nullable', 'date_format:H:i'],
            'dni.*.godzina_zamkniecia' => ['required_if:dni.*.czy_czynne,1', 'nullable', 'date_format:H:i'],
        ]);

        $routePrefix = auth()->user()->rola === 'administrator' ? 'admin.schedule.index' : 'pracownik.schedule.index';

        foreach ($data['dni'] as $dzien) {
            $czynne = isset($dzien['czy_czynne']) && $dzien['czy_czynne'];

            GodzinyPracy::where('id', $dzien['id'])->update([
                'czy_czynne' => $czynne,
                'godzina_otwarcia' => $czynne ? $dzien['godzina_otwarcia'] : null,
                'godzina_zamkniecia' => $czynne ? $dzien['godzina_zamkniecia'] : null,
            ]);
        }

        return redirect()->route($routePrefix)->with('status', 'Godziny pracy zostały zaktualizowane.');
    }
}