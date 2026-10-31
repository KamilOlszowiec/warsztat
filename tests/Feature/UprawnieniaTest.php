<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TworzyDaneWarsztatu;
use Tests\TestCase;

/**
 * Kontrola dostępu: role oraz zasada, że pracownik zarządza tylko swoimi wizytami.
 */
class UprawnieniaTest extends TestCase
{
    use RefreshDatabase, TworzyDaneWarsztatu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->przygotujWarsztat();
    }

    public function test_pracownik_zatwierdza_swoja_wizyte_ale_nie_cudza(): void
    {
        $ja = $this->mechanik([], '08:00', '12:00');
        $kolega = $this->mechanik([], '12:00', '16:00');
        $usluga = $this->usluga(null, 60);
        $klient = $this->uzytkownik('klient');
        $pojazd = $this->pojazd($klient);

        $moja = $this->rezerwacja($klient, $pojazd, [$usluga], '09:00', $ja->id_uzytkownika);
        $cudza = $this->rezerwacja($klient, $pojazd, [$usluga], '13:00', $kolega->id_uzytkownika);

        $this->actingAs($ja)
            ->post(route('pracownik.reservations.approve', $cudza))
            ->assertForbidden();

        $this->actingAs($ja)
            ->post(route('pracownik.reservations.approve', $moja))
            ->assertRedirect();

        $this->assertSame('zatwierdzona', $moja->fresh()->status);
        $this->assertSame('oczekująca', $cudza->fresh()->status);
    }

    public function test_przydzial_mechanika_jest_tylko_dla_administratora(): void
    {
        $pracownik = $this->mechanik();
        $admin = $this->uzytkownik('administrator');
        $klient = $this->uzytkownik('klient');
        $rezerwacja = $this->rezerwacja($klient, $this->pojazd($klient), [$this->usluga(null)], '09:00');

        $this->actingAs($pracownik)
            ->get(route('pracownik.reservations.przydzial', $rezerwacja))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('pracownik.reservations.przydzial', $rezerwacja))
            ->assertOk();
    }

    public function test_klient_nie_widzi_cudzej_rezerwacji(): void
    {
        $this->mechanik();
        $wlasciciel = $this->uzytkownik('klient');
        $obcy = $this->uzytkownik('klient');
        $rezerwacja = $this->rezerwacja($wlasciciel, $this->pojazd($wlasciciel), [$this->usluga(null)], '09:00');

        $this->actingAs($obcy)
            ->get(route('klient.reservations.show', $rezerwacja))
            ->assertForbidden();

        $this->actingAs($wlasciciel)
            ->get(route('klient.reservations.show', $rezerwacja))
            ->assertOk();
    }

    public function test_klient_nie_ma_dostepu_do_panelu_pracownika(): void
    {
        $this->actingAs($this->uzytkownik('klient'))
            ->get(route('pracownik.reservations.plan'))
            ->assertForbidden();
    }

    public function test_niezalogowany_jest_przekierowany_do_logowania(): void
    {
        $this->get(route('pracownik.reservations.plan'))
            ->assertRedirect(route('login'));
    }
}
