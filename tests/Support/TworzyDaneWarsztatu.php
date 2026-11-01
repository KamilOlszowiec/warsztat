<?php

namespace Tests\Support;

use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Pojazd;
use App\Models\Rezerwacja;
use App\Models\Specjalizacja;
use App\Models\Usluga;
use App\Models\Uzytkownik;
use App\Services\DostepnoscRezerwacjiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Metody pomocnicze do budowania danych testowych warsztatu.
 * Wszystkie testy działają w ustalonym czasie: poniedziałek 16.11.2026, 7:00,
 * a terminy sprawdzane są na wtorek 17.11.2026.
 */
trait TworzyDaneWarsztatu
{
    protected string $dzien = '2026-11-17';

    // Każda usługa wymaga specjalizacji – usługi „ogólne” w testach dostają tę domyślną
    private ?Specjalizacja $specjalizacjaOgolna = null;

    protected function specjalizacjaOgolna(): Specjalizacja
    {
        return $this->specjalizacjaOgolna ??= $this->specjalizacja('Mechanika ogólna');
    }

    protected function przygotujWarsztat(): void
    {
        $this->travelTo(Carbon::parse('2026-11-16 07:00'));

        // Pon–pt 8:00–16:00, weekend nieczynny
        foreach (range(1, 7) as $dzien) {
            GodzinyPracy::create([
                'dzien_tygodnia'     => $dzien,
                'godzina_otwarcia'   => $dzien <= 5 ? '08:00:00' : null,
                'godzina_zamkniecia' => $dzien <= 5 ? '16:00:00' : null,
                'czy_czynne'         => $dzien <= 5,
            ]);
        }
    }

    protected function uzytkownik(string $rola, array $dane = []): Uzytkownik
    {
        static $numer = 0;
        $numer++;

        return Uzytkownik::create(array_merge([
            'imie'              => 'Test',
            'nazwisko'          => ucfirst($rola) . $numer,
            'email'             => $rola . $numer . '@test.pl',
            'telefon'           => '600000' . str_pad((string) $numer, 3, '0', STR_PAD_LEFT),
            'password'          => Hash::make('haslo1234'),
            'rola'              => $rola,
            'email_verified_at' => now(),
        ], $dane));
    }

    protected function specjalizacja(string $nazwa): Specjalizacja
    {
        return Specjalizacja::create(['nazwa' => $nazwa]);
    }

    /**
     * Mechanik z podanymi specjalizacjami (zawsze także ogólną) i zmianą we wtorek (domyślnie 8:00–16:00).
     */
    protected function mechanik(array $specjalizacje = [], string $od = '08:00', string $do = '16:00'): Uzytkownik
    {
        $mechanik = $this->uzytkownik('pracownik');
        $mechanik->specjalizacje()->attach(
            collect($specjalizacje)->push($this->specjalizacjaOgolna())->pluck('id_specjalizacji')->unique()
        );

        GrafikPracownika::create([
            'id_pracownika' => $mechanik->id_uzytkownika,
            'data'          => $this->dzien,
            'godzina_od'    => $od,
            'godzina_do'    => $do,
        ]);

        return $mechanik;
    }

    protected function usluga(?Specjalizacja $specjalizacja, int $czas = 60, float $cena = 100): Usluga
    {
        return Usluga::create([
            'nazwa'            => 'Usługa ' . ($specjalizacja?->nazwa ?? 'ogólna') . ' ' . $czas,
            'cena'             => $cena,
            'czas_trwania'     => $czas,
            'id_specjalizacji' => ($specjalizacja ?? $this->specjalizacjaOgolna())->id_specjalizacji,
            'czy_aktywna'      => true,
        ]);
    }

    protected function pojazd(Uzytkownik $klient, string $numer = 'LU12345'): Pojazd
    {
        return Pojazd::create([
            'id_uzytkownika'      => $klient->id_uzytkownika,
            'marka'               => 'Toyota',
            'model'               => 'Corolla',
            'numer_rejestracyjny' => $numer,
        ]);
    }

    /**
     * Rezerwacja zapisana przez serwis, tak jak robi to aplikacja.
     */
    protected function rezerwacja(Uzytkownik $klient, Pojazd $pojazd, array $uslugi, string $godzina, ?int $mechanik = null, string $status = 'oczekująca'): ?Rezerwacja
    {
        return app(DostepnoscRezerwacjiService::class)->utworzRezerwacje([
            'id_klienta' => $klient->id_uzytkownika,
            'id_pojazdu' => $pojazd->id_pojazdu,
            'status'     => $status,
        ], collect($uslugi), Carbon::parse($this->dzien . ' ' . $godzina), $mechanik);
    }
}
