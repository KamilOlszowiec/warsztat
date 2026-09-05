<?php

// app/Http/Controllers/Admin/RezerwacjaController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rezerwacja;
use Illuminate\Http\Request;

class RezerwacjaController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'wszystkie');

        $query = Rezerwacja::with(['klient', 'pojazd', 'usluga', 'pracownik'])
            ->orderByDesc('termin_rozpoczecia');

        if ($status !== 'wszystkie') {
            $query->where('status', $status);
        }

        $rezerwacje = $query->paginate(20)->withQueryString();

        return view('admin.reservations.index', compact('rezerwacje', 'status'));
    }

    public function destroy(Rezerwacja $reservation)
    {
        $reservation->delete();

        return back()->with('status', 'Rezerwacja została trwale usunięta.');
    }
}