<?php

namespace Tests\Feature;

use App\Models\GodzinyPracy;
use App\Models\GrafikPracownika;
use App\Models\Pojazd;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\TworzyDaneWarsztatu;
use Tests\TestCase;

/**
 * Ograniczenia pilnowane przez samą bazę danych – niezależnie od walidacji w aplikacji.
 */
class IntegralnoscBazyTest extends TestCase
{
    use RefreshDatabase, TworzyDaneWarsztatu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->przygotujWarsztat();
    }

    public function test_czas_uslugi_musi_byc_wielokrotnoscia_30_minut(): void
    {
        $this->expectException(QueryException::class);
        $this->usluga(null, 45);
    }

    public function test_usluga_musi_miec_specjalizacje(): void
    {
        $this->expectException(QueryException::class);
        DB::table('uslugi')->insert(['nazwa' => 'Bez specjalizacji', 'cena' => 100, 'czas_trwania' => 30, 'czy_aktywna' => true]);
    }

    public function test_koniec_zmiany_musi_byc_po_jej_poczatku(): void
    {
        $mechanik = $this->uzytkownik('pracownik');

        $this->expectException(QueryException::class);
        GrafikPracownika::create([
            'id_pracownika' => $mechanik->id_uzytkownika,
            'data'          => $this->dzien,
            'godzina_od'    => '14:00',
            'godzina_do'    => '10:00',
        ]);
    }

    public function test_jeden_wpis_godzin_pracy_na_dzien_tygodnia(): void
    {
        $this->expectException(QueryException::class);
        GodzinyPracy::create(['dzien_tygodnia' => 1, 'godzina_otwarcia' => '09:00:00', 'godzina_zamkniecia' => '17:00:00', 'czy_czynne' => true]);
    }

    public function test_numer_vin_jest_unikalny(): void
    {
        $klient = $this->uzytkownik('klient');
        Pojazd::create(['id_uzytkownika' => $klient->id_uzytkownika, 'marka' => 'Opel', 'model' => 'Astra', 'numer_rejestracyjny' => 'LU1', 'vin' => 'WVWZZZ1JZXW000001']);

        $this->expectException(QueryException::class);
        Pojazd::create(['id_uzytkownika' => $klient->id_uzytkownika, 'marka' => 'Opel', 'model' => 'Astra', 'numer_rejestracyjny' => 'LU2', 'vin' => 'WVWZZZ1JZXW000001']);
    }
}
