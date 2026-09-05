<?php

// app/Http/Controllers/Pracownik/KlientController.php
namespace App\Http\Controllers\Pracownik;

use App\Http\Controllers\Controller;
use App\Models\Uzytkownik;

class KlientController extends Controller
{
    public function index()
    {
        $klienci = Uzytkownik::where('rola', 'klient')
            ->with('pojazdy')
            ->orderBy('nazwisko')
            ->get();

        return view('pracownik.clients.index', compact('klienci'));
    }
}