<?php

namespace Tests\Feature;

use App\Models\DzienWolny;
use App\Models\GodzinyPracy;
use App\Services\DostepnoscRezerwacjiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TworzyDaneWarsztatu;
use Tests\TestCase;

/**
 * Wyznaczanie wolnych terminów i ochrona przed podwójną rezerwacją.
 */
class WyznaczanieTerminowTest extends TestCase
{
    use RefreshDatabase, TworzyDaneWarsztatu;

    private DostepnoscRezerwacjiService $dostepnosc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->przygotujWarsztat();
        $this->dostepnosc = app(DostepnoscRezerwacjiService::class);
    }

    public function test_godziny_sa_wyznaczane_co_30_minut_w_ramach_zmiany_mechanika(): void
    {
        $this->mechanik([], '10:00', '12:00');
        $usluga = $this->usluga(null, 60);

        $godziny = $this->dostepnosc->dostepneGodziny(collect([$usluga]), $this->dzien);

        // Wizyta 60 min musi zmieścić się w zmianie 10:00–12:00
        $this->assertSame(['10:00', '10:30', '11:00'], $godziny);
    }

    public function test_w_dzien_wolny_warsztatu_nie_ma_wolnych_godzin(): void
    {
        $this->mechanik();
        DzienWolny::create(['data' => $this->dzien, 'opis' => 'Inwentaryzacja']);

        $godziny = $this->dostepnosc->dostepneGodziny(collect([$this->usluga(null)]), $this->dzien);

        $this->assertSame([], $godziny);
    }

    public function test_gdy_warsztat_jest_nieczynny_nie_ma_wolnych_godzin(): void
    {
        $this->mechanik();
        GodzinyPracy::where('dzien_tygodnia', 2)->update(['czy_czynne' => false]);

        $godziny = $this->dostepnosc->dostepneGodziny(collect([$this->usluga(null)]), $this->dzien);

        $this->assertSame([], $godziny);
    }

    public function test_zajety_termin_mechanika_nie_jest_dostepny(): void
    {
        $this->mechanik([], '08:00', '12:00');
        $usluga = $this->usluga(null, 60);
        $klient = $this->uzytkownik('klient');
        $this->rezerwacja($klient, $this->pojazd($klient), [$usluga], '09:00');

        $godziny = $this->dostepnosc->dostepneGodziny(collect([$usluga]), $this->dzien);

        // 8:30 i 9:30 nachodziłyby na wizytę 9:00–10:00
        $this->assertSame(['08:00', '10:00', '10:30', '11:00'], $godziny);
    }

    public function test_nie_da_sie_zarezerwowac_zajetego_terminu(): void
    {
        $this->mechanik();
        $usluga = $this->usluga(null, 60);
        $klient = $this->uzytkownik('klient');
        $pojazd = $this->pojazd($klient);

        $pierwsza = $this->rezerwacja($klient, $pojazd, [$usluga], '09:00');
        $druga = $this->rezerwacja($klient, $pojazd, [$usluga], '09:30');

        $this->assertNotNull($pierwsza);
        $this->assertNull($druga, 'Jedyny mechanik jest zajęty – druga rezerwacja nie może powstać.');
        $this->assertDatabaseCount('rezerwacje', 1);
    }

    public function test_drugi_mechanik_przejmuje_rownolegla_wizyte(): void
    {
        $pierwszy = $this->mechanik();
        $drugi = $this->mechanik();
        $usluga = $this->usluga(null, 60);
        $klient = $this->uzytkownik('klient');
        $pojazd = $this->pojazd($klient);

        $a = $this->rezerwacja($klient, $pojazd, [$usluga], '09:00');
        $b = $this->rezerwacja($klient, $pojazd, [$usluga], '09:00');

        $this->assertNotNull($b);
        $this->assertEqualsCanonicalizing(
            [$pierwszy->id_uzytkownika, $drugi->id_uzytkownika],
            [$a->id_mechanika, $b->id_mechanika]
        );
    }

    public function test_mechanik_musi_miec_wszystkie_specjalizacje_wybranych_uslug(): void
    {
        $elektryka = $this->specjalizacja('Elektryka');
        $klimatyzacja = $this->specjalizacja('Klimatyzacja');

        $this->mechanik([$elektryka]);
        $wszechstronny = $this->mechanik([$elektryka, $klimatyzacja]);

        $uslugi = collect([$this->usluga($elektryka, 30), $this->usluga($klimatyzacja, 30)]);
        $klient = $this->uzytkownik('klient');

        $rezerwacja = $this->rezerwacja($klient, $this->pojazd($klient), $uslugi->all(), '10:00');

        $this->assertSame($wszechstronny->id_uzytkownika, $rezerwacja->id_mechanika);
    }

    public function test_bez_mechanika_z_wymagana_specjalizacja_nie_ma_terminow(): void
    {
        $this->mechanik([$this->specjalizacja('Mechanika ogólna')]);
        $usluga = $this->usluga($this->specjalizacja('Diagnostyka'));

        $this->assertFalse($this->dostepnosc->czyKtosMozeWykonac(collect([$usluga])));
        $this->assertSame([], $this->dostepnosc->dostepneGodziny(collect([$usluga]), $this->dzien));
    }

    public function test_wybrany_mechanik_zaweza_wolne_godziny(): void
    {
        $this->mechanik([], '08:00', '10:00');
        $popoludniowy = $this->mechanik([], '14:00', '16:00');
        $usluga = $this->usluga(null, 60);

        $godziny = $this->dostepnosc->dostepneGodziny(collect([$usluga]), $this->dzien, null, null, $popoludniowy->id_uzytkownika);

        $this->assertSame(['14:00', '14:30', '15:00'], $godziny);
    }
}
