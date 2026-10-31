<?php
namespace Database\Seeders;

use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Pojazd;
use App\Models\Rezerwacja;
use App\Models\Specjalizacja;
use App\Models\Usluga;
use App\Models\Uzytkownik;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Specjalizacje
        $ogolna      = Specjalizacja::create(['nazwa' => 'Mechanika ogólna']);
        $elektryka   = Specjalizacja::create(['nazwa' => 'Elektryka samochodowa']);
        $klimatyzacja = Specjalizacja::create(['nazwa' => 'Klimatyzacja']);
        $diagnostyka = Specjalizacja::create(['nazwa' => 'Diagnostyka']);

        // Administrator
        Uzytkownik::create([
            'imie' => 'Anna', 'nazwisko' => 'Kowalska',
            'email' => 'admin@warsztat.pl', 'password' => Hash::make('haslo1234'),
            'rola' => 'administrator', 'email_verified_at' => now(),
        ]);

        // Pracownicy (mechanicy)
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
        $pracownik3 = Uzytkownik::create([
            'imie' => 'Adam', 'nazwisko' => 'Kaczmarek',
            'email' => 'adam@warsztat.pl', 'password' => Hash::make('haslo1234'),
            'rola' => 'pracownik', 'email_verified_at' => now(),
        ]);

        // Specjalizacje pracowników
        $ogolna->pracownicy()->attach([$pracownik1->id_uzytkownika, $pracownik2->id_uzytkownika]);
        $diagnostyka->pracownicy()->attach([$pracownik2->id_uzytkownika]);
        $elektryka->pracownicy()->attach([$pracownik2->id_uzytkownika, $pracownik3->id_uzytkownika]);
        $klimatyzacja->pracownicy()->attach([$pracownik3->id_uzytkownika]);

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

        // Usługi z wymaganą specjalizacją
        $wymianaOleju = Usluga::create([
            'nazwa' => 'Wymiana oleju', 'opis' => 'Wymiana oleju silnikowego wraz z filtrem',
            'cena' => 150.00, 'czas_trwania' => 30, 'id_specjalizacji' => $ogolna->id_specjalizacji, 'czy_aktywna' => true,
        ]);
        $przeglad = Usluga::create([
            'nazwa' => 'Przegląd okresowy', 'opis' => 'Pełny przegląd techniczny pojazdu',
            'cena' => 250.00, 'czas_trwania' => 60, 'id_specjalizacji' => $ogolna->id_specjalizacji, 'czy_aktywna' => true,
        ]);
        $diagnostykaKomp = Usluga::create([
            'nazwa' => 'Diagnostyka komputerowa', 'opis' => 'Odczyt i analiza błędów sterownika',
            'cena' => 120.00, 'czas_trwania' => 30, 'id_specjalizacji' => $diagnostyka->id_specjalizacji, 'czy_aktywna' => true,
        ]);
        $hamulce = Usluga::create([
            'nazwa' => 'Wymiana klocków hamulcowych', 'opis' => 'Wymiana klocków hamulcowych jednej osi',
            'cena' => 200.00, 'czas_trwania' => 60, 'id_specjalizacji' => $ogolna->id_specjalizacji, 'czy_aktywna' => true,
        ]);
        Usluga::create([
            'nazwa' => 'Serwis klimatyzacji', 'opis' => 'Odgrzybianie i uzupełnienie czynnika chłodniczego',
            'cena' => 180.00, 'czas_trwania' => 60, 'id_specjalizacji' => $klimatyzacja->id_specjalizacji, 'czy_aktywna' => true,
        ]);
        Usluga::create([
            'nazwa' => 'Naprawa instalacji elektrycznej', 'opis' => 'Lokalizacja i usunięcie usterki instalacji',
            'cena' => 220.00, 'czas_trwania' => 90, 'id_specjalizacji' => $elektryka->id_specjalizacji, 'czy_aktywna' => true,
        ]);

        // Dzień wolny warsztatu: najbliższe 11 listopada
        $swieto = Carbon::create(now()->year, 11, 11);
        if ($swieto->isPast()) {
            $swieto->addYear();
        }
        DzienWolny::create(['data' => $swieto->toDateString(), 'opis' => 'Narodowe Święto Niepodległości']);

        // Grafik mechaników na 6 tygodni wg godzin pracy warsztatu
        $urlopTomasza = $this->dzienRoboczy(4);
        $this->grafik([$pracownik1, $pracownik2, $pracownik3], 42, [$swieto], [
            $pracownik2->id_uzytkownika => [$urlopTomasza->toDateString()],
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
        $this->rezerwacja([
            'id_klienta' => $klient1->id_uzytkownika, 'id_pojazdu' => $pojazd1->id_pojazdu,
            'id_mechanika' => $pracownik2->id_uzytkownika,
            'termin_rozpoczecia' => $this->dzienRoboczy(2)->setTime(10, 0),
            'status' => 'zatwierdzona',
        ], [$wymianaOleju, $diagnostykaKomp]); // dwie usługi – tylko Tomasz ma obie specjalizacje

        $this->rezerwacja([
            'id_klienta' => $klient2->id_uzytkownika, 'id_pojazdu' => $pojazd2->id_pojazdu,
            'id_mechanika' => $pracownik1->id_uzytkownika,
            'termin_rozpoczecia' => $this->dzienRoboczy(3)->setTime(11, 0),
            'status' => 'oczekująca',
        ], [$przeglad]);

        $this->rezerwacja([
            'id_klienta' => $klient1->id_uzytkownika, 'id_pojazdu' => $pojazd1->id_pojazdu,
            'id_mechanika' => $pracownik2->id_uzytkownika,
            'termin_rozpoczecia' => $this->dzienRoboczy(3)->setTime(11, 0),
            'status' => 'oczekująca',
        ], [$hamulce]); // ta sama godzina: obaj mechanicy ogólni zajęci

        $this->rezerwacja([
            'id_klienta' => $klient1->id_uzytkownika, 'id_pojazdu' => $pojazd1->id_pojazdu,
            'id_mechanika' => $pracownik2->id_uzytkownika,
            'termin_rozpoczecia' => $this->dzienRoboczy(-10)->setTime(9, 0),
            'status' => 'zakończona',
            // Przykładowy protokół z wizyty
            'przebieg' => 184250,
            'opis_prac' => 'Diagnostyka komputerowa: odczyt błędów silnika, błąd P0171 (zbyt uboga mieszanka). Oczyszczenie przepływomierza, skasowanie błędów, jazda próbna bez usterek.',
            'zalecenia' => 'Przy następnej wizycie sprawdzić szczelność układu dolotowego.',
            'data_nastepnej_wizyty' => $this->dzienRoboczy(-10)->addMonths(6)->toDateString(),
            'data_zakonczenia' => $this->dzienRoboczy(-10)->setTime(10, 0),
        ], [$diagnostykaKomp]);

        $this->rezerwacja([
            'id_klienta' => $klient2->id_uzytkownika, 'id_pojazdu' => $pojazd2->id_pojazdu,
            'id_mechanika' => $pracownik1->id_uzytkownika,
            'termin_rozpoczecia' => $this->dzienRoboczy(-5)->setTime(14, 0),
            'status' => 'odrzucona', 'powod_odrzucenia' => 'Brak dostępnych części na magazynie.',
        ], [$wymianaOleju]);
    }

    /**
     * Zmiany pracowników na $dni dni od dziś, zgodnie z godzinami pracy warsztatu.
     * Pomija dni zamknięte, dni wolne warsztatu i urlopy ($urlopy: id_pracownika => [daty]).
     */
    private function grafik(array $pracownicy, int $dni, array $dniWolne, array $urlopy): void
    {
        $godzinyPracy = GodzinyPracy::all()->keyBy('dzien_tygodnia');

        if ($godzinyPracy->isEmpty()) {
            $this->command->warn('Brak godzin pracy warsztatu – grafik nie został utworzony. Uruchom seeder godzin pracy przed DemoDataSeeder.');
            return;
        }

        $wolne = array_map(fn (Carbon $d) => $d->toDateString(), $dniWolne);

        for ($i = 0; $i < $dni; $i++) {
            $dzien = Carbon::today()->addDays($i);
            $godziny = $godzinyPracy->get($dzien->dayOfWeekIso);

            if (!$godziny || !$godziny->czy_czynne || in_array($dzien->toDateString(), $wolne, true)) {
                continue;
            }

            foreach ($pracownicy as $p) {
                if (in_array($dzien->toDateString(), $urlopy[$p->id_uzytkownika] ?? [], true)) {
                    continue;
                }

                GrafikPracownika::create([
                    'id_pracownika' => $p->id_uzytkownika,
                    'data'          => $dzien->toDateString(),
                    'godzina_od'    => $godziny->godzina_otwarcia,
                    'godzina_do'    => $godziny->godzina_zamkniecia,
                ]);
            }
        }
    }

    /**
     * Tworzy rezerwację z listą usług; koniec wizyty = start + suma czasów usług.
     */
    private function rezerwacja(array $dane, array $uslugi): Rezerwacja
    {
        $czas = array_sum(array_map(fn ($u) => $u->czas_trwania, $uslugi));
        $dane['termin_zakonczenia'] = $dane['termin_rozpoczecia']->copy()->addMinutes($czas);

        $rezerwacja = Rezerwacja::create($dane);

        foreach ($uslugi as $u) {
            $rezerwacja->uslugi()->attach($u->id_uslugi, [
                'cena' => $u->cena,
                'czas_trwania' => $u->czas_trwania,
            ]);
        }

        return $rezerwacja;
    }

    /**
     * Data przesunięta o $dni od dziś (ujemne = przeszłość), z pominięciem weekendu.
     */
    private function dzienRoboczy(int $dni): Carbon
    {
        $data = Carbon::today()->addDays($dni);
        $krok = $dni >= 0 ? 1 : -1;

        while ($data->isWeekend()) {
            $data->addDays($krok);
        }

        return $data;
    }
}