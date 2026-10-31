<?php

namespace Tests\Feature;

use App\Notifications\RezerwacjaZakonczona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\TworzyDaneWarsztatu;
use Tests\TestCase;

/**
 * Zakończenie wizyty z protokołem i zasady jego poprawiania.
 */
class ProtokolTest extends TestCase
{
    use RefreshDatabase, TworzyDaneWarsztatu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->przygotujWarsztat();
        Notification::fake();
    }

    private function protokol(array $zmiany = []): array
    {
        return array_merge([
            'przebieg'      => 150000,
            'opis_prac'     => 'Wymiana oleju i filtra.',
            'zalecenia'     => 'Wymienić klocki przy następnej wizycie.',
            'koszt_koncowy' => '180.00',
        ], $zmiany);
    }

    public function test_mechanik_konczy_wizyte_z_protokolem(): void
    {
        $mechanik = $this->mechanik();
        $klient = $this->uzytkownik('klient');
        $rezerwacja = $this->rezerwacja($klient, $this->pojazd($klient), [$this->usluga(null)], '09:00', null, 'zatwierdzona');

        $this->actingAs($mechanik)
            ->put(route('pracownik.reservations.protokol.update', $rezerwacja), $this->protokol())
            ->assertRedirect();

        $rezerwacja->refresh();
        $this->assertSame('zakończona', $rezerwacja->status);
        $this->assertSame(150000, (int) $rezerwacja->przebieg);
        $this->assertEquals(180.00, $rezerwacja->kwota_koncowa);
        $this->assertNotNull($rezerwacja->data_zakonczenia);
        Notification::assertSentTo($klient, RezerwacjaZakonczona::class);
    }

    public function test_bez_opisu_prac_wizyta_nie_zostaje_zakonczona(): void
    {
        $mechanik = $this->mechanik();
        $klient = $this->uzytkownik('klient');
        $rezerwacja = $this->rezerwacja($klient, $this->pojazd($klient), [$this->usluga(null)], '09:00', null, 'zatwierdzona');

        $this->actingAs($mechanik)
            ->put(route('pracownik.reservations.protokol.update', $rezerwacja), $this->protokol(['opis_prac' => '']))
            ->assertSessionHasErrors('opis_prac');

        $this->assertSame('zatwierdzona', $rezerwacja->fresh()->status);
    }

    public function test_przebieg_mniejszy_niz_poprzednio_wymaga_potwierdzenia(): void
    {
        $mechanik = $this->mechanik();
        $klient = $this->uzytkownik('klient');
        $pojazd = $this->pojazd($klient);
        $usluga = $this->usluga(null, 30);

        $poprzednia = $this->rezerwacja($klient, $pojazd, [$usluga], '08:00', null, 'zatwierdzona');
        $poprzednia->update(['status' => 'zakończona', 'przebieg' => 200000, 'opis_prac' => 'Przegląd.', 'data_zakonczenia' => now()]);
        $obecna = $this->rezerwacja($klient, $pojazd, [$usluga], '10:00', null, 'zatwierdzona');

        $this->actingAs($mechanik)
            ->put(route('pracownik.reservations.protokol.update', $obecna), $this->protokol(['przebieg' => 150000]))
            ->assertSessionHasErrors('przebieg');

        $this->actingAs($mechanik)
            ->put(route('pracownik.reservations.protokol.update', $obecna), $this->protokol(['przebieg' => 150000, 'potwierdz_przebieg' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertSame('zakończona', $obecna->fresh()->status);
    }

    public function test_protokol_poprawia_mechanik_tego_dnia_a_pozniej_tylko_administrator(): void
    {
        $mechanik = $this->mechanik();
        $innyPracownik = $this->mechanik();
        $admin = $this->uzytkownik('administrator');
        $klient = $this->uzytkownik('klient');
        $rezerwacja = $this->rezerwacja($klient, $this->pojazd($klient), [$this->usluga(null)], '09:00', $mechanik->id_uzytkownika, 'zatwierdzona');

        $this->actingAs($mechanik)->put(route('pracownik.reservations.protokol.update', $rezerwacja), $this->protokol());

        // Inny pracownik – nigdy
        $this->actingAs($innyPracownik)->get(route('pracownik.reservations.protokol', $rezerwacja))->assertForbidden();

        // Mechanik – tego samego dnia tak
        $this->actingAs($mechanik)->get(route('pracownik.reservations.protokol', $rezerwacja))->assertOk();

        // Następnego dnia mechanik już nie, administrator tak
        $this->travel(1)->days();
        $this->actingAs($mechanik)->get(route('pracownik.reservations.protokol', $rezerwacja))->assertForbidden();
        $this->actingAs($admin)->get(route('pracownik.reservations.protokol', $rezerwacja))->assertOk();
    }
}
