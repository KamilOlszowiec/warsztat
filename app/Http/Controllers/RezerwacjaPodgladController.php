<?php
namespace App\Http\Controllers;

use App\Models\Rezerwacja;
use Illuminate\Http\Request;

class RezerwacjaPodgladController extends Controller
{
    public function show(Request $request, Rezerwacja $reservation)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Link jest nieprawidłowy lub wygasł.');
        }

        $reservation->load(['uslugi', 'pojazd', 'klient']);

        return view('reservations.public-show', ['rezerwacja' => $reservation]);
    }
}