<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;

class RezerwacjaController extends Controller
{
    /**
     * Trwałe usunięcie rezerwacji – tylko dla administratora.
     * Lista rezerwacji jest wspólna z pracownikiem (pracownik.reservations.index).
     */
    public function destroy(Rezerwacja $reservation)
    {
        $reservation->delete();

        return back()->with('status', 'Rezerwacja została trwale usunięta.');
    }
}