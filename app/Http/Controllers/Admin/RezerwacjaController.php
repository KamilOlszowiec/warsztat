<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;

class RezerwacjaController extends Controller
{
    public function destroy(Rezerwacja $reservation)
    {
        $reservation->delete();

        return back()->with('status', 'Rezerwacja została trwale usunięta.');
    }
}