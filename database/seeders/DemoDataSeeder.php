<?php
namespace Database\Seeders;

use App\Models\Pojazd;
use App\Models\Rezerwacja;
use App\Models\Usluga;
use App\Models\Uzytkownik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Administrator
        $admin = Uzytkownik::create([
            'imie' => 'Anna', 'nazwisko' => 'Kowalska',
            'email' => 'admin@warsztat.pl', 'password' => Hash::make('haslo1234'),
            'rola' => 'administrator', 'email_verified_at' => now(),
        ]);

        // Pracownicy
        $pracownik1 = Uzytkownik::create([
            'imie' => 'Piotr', 'nazwisko' => 'Nowak',
            'email' => 'piotr@warsztat.pl', 'password' => Hash::make('haslo1234'),
            'rola' => 'pracownik', 'email_verified_at' => now(),
        ]);
        $pracownik2 = Uzytkownik::create([
            'imie' => 'Tomasz', 'nazwisko' => 'Wiśniewski',
            'email' => 'tomasz@warsztat.pl', 'password' => Hash::make('haslo1234'),
            'rola' => 'pracownik', 'email_verified_at' => now(),
        ]);

        // Klienci
        $klient1 = Uzytkownik::create([
            'imie' => 'Jan', 'nazwisko' => 'Zieliński',
            'email' => 'jan@example.com', 'password' => Hash::make('haslo1234'),
            'telefon' => '500100200', 'rola' => 'klient', 'email_verified_at' => now(),
        ]);
        $klient2 = Uzytkownik::create([
            'imie' => 'Maria', 'nazwisko' => 'Lewandowska',
            'email' => 'maria@example.com', 'password' => Hash::make('haslo1234'),
            'telefon' => '500300400', 'rola' => 'klient', 'email_verified_at' => now(),
        ]);

        // Usługi
        $wymianaOleju = Usluga::create([
            'nazwa' => 'Wymiana oleju', 'opis' => 'Wymiana oleju silnikowego wraz z filtrem',
            'cena' => 150.00, 'czas_trwania' => 30, 'maks_jednoczesnie' => 3, 'czy_aktywna' => true,
        ]);
        $przeglad = Usluga::create([
            'nazwa' => 'Przegląd okresowy', 'opis' => 'Pełny przegląd techniczny pojazdu',
            'cena' => 250.00, 'czas_trwania' => 60, 'maks_jednoczesnie' => 2, 'czy_aktywna' => true,
        ]);
        $diagnostyka = Usluga::create([
            'nazwa' => 'Diagnostyka komputerowa', 'opis' => 'Odczyt i analiza błędów sterownika',
            'cena' => 120.00, 'czas_trwania' => 30, 'maks_jednoczesnie' => 1, 'czy_aktywna' => true,
        ]);

        // Pojazdy
        $pojazd1 = Pojazd::create([
            'id_uzytkownika' => $klient1->id_uzytkownika,
            'marka' => 'Toyota', 'model' => 'Corolla', 'rok_produkcji' => 2018,
            'numer_rejestracyjny' => 'LU12345', 'vin' => 'JT2BF22K1W0123456',
        ]);
        $pojazd2 = Pojazd::create([
            'id_uzytkownika' => $klient2->id_uzytkownika,
            'marka' => 'Volkswagen', 'model' => 'Golf', 'rok_produkcji' => 2020,
            'numer_rejestracyjny' => 'LU54321', 'vin' => 'WVWZZZ1KZAW123456',
        ]);

        // Rezerwacje w różnych stanach
        Rezerwacja::create([
            'id_klienta' => $klient1->id_uzytkownika, 'id_pojazdu' => $pojazd1->id_pojazdu,
            'id_uslugi' => $wymianaOleju->id_uslugi, 'id_pracownika' => $pracownik1->id_uzytkownika,
            'termin_rozpoczecia' => Carbon::now()->addDays(2)->setTime(10, 0),
            'termin_zakonczenia' => Carbon::now()->addDays(2)->setTime(10, 30),
            'status' => 'zatwierdzona',
        ]);

        Rezerwacja::create([
            'id_klienta' => $klient2->id_uzytkownika, 'id_pojazdu' => $pojazd2->id_pojazdu,
            'id_uslugi' => $przeglad->id_uslugi,
            'termin_rozpoczecia' => Carbon::now()->addDays(3)->setTime(11, 0),
            'termin_zakonczenia' => Carbon::now()->addDays(3)->setTime(12, 0),
            'status' => 'oczekująca',
        ]);

        Rezerwacja::create([
            'id_klienta' => $klient1->id_uzytkownika, 'id_pojazdu' => $pojazd1->id_pojazdu,
            'id_uslugi' => $diagnostyka->id_uslugi, 'id_pracownika' => $pracownik2->id_uzytkownika,
            'termin_rozpoczecia' => Carbon::now()->subDays(10)->setTime(9, 0),
            'termin_zakonczenia' => Carbon::now()->subDays(10)->setTime(9, 30),
            'status' => 'zakończona',
        ]);

        Rezerwacja::create([
            'id_klienta' => $klient2->id_uzytkownika, 'id_pojazdu' => $pojazd2->id_pojazdu,
            'id_uslugi' => $wymianaOleju->id_uslugi, 'id_pracownika' => $pracownik1->id_uzytkownika,
            'termin_rozpoczecia' => Carbon::now()->subDays(5)->setTime(14, 0),
            'termin_zakonczenia' => Carbon::now()->subDays(5)->setTime(14, 30),
            'status' => 'odrzucona', 'powod_odrzucenia' => 'Brak dostępnych części na magazynie.',
        ]);
    }
}
