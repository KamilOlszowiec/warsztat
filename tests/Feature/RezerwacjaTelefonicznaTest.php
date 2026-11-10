<?php

namespace Tests\Feature;

use App\Models\Rezerwacja;
use App\Models\Uzytkownik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\TworzyDaneWarsztatu;
use Tests\TestCase;

class RezerwacjaTelefonicznaTest extends TestCase
{
    use RefreshDatabase, TworzyDaneWarsztatu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->przygotujWarsztat();
        Notification::fake();
    }

    private function daneNowegoKlienta(int $idUslugi, string $godzina, array $zmiany = []): array
    {
        return array_merge([
            'uslugi'              => [$idUslugi],
            'data'                => $this->dzien,
            'godzina'             => $godzina,
            'klient_tryb'         => 'nowy',
            'imie'                => 'Marek',
            'nazwisko'            => 'Telefoniczny',
            'telefon'             => '511222333',
            'email'               => '',
            'pojazd_tryb'         => 'nowy',
            'marka'               => 'Skoda',
            'model'               => 'Octavia',
            'numer_rejestracyjny' => 'LU 98765',
        ], $zmiany);
    }

    public function test_pracownik_zapisuje_nowego_klienta_bez_emaila(): void
    {
        $pracownik = $this->mechanik();
        $usluga = $this->usluga(null, 60);

        $this->actingAs($pracownik)
            ->post(route('pracownik.reservations.nowa.store'), $this->daneNowegoKlienta($usluga->id_uslugi, '10:00'))
            ->assertRedirect();

        $klient = Uzytkownik::where('telefon', '511222333')->firstOrFail();
        $rezerwacja = Rezerwacja::where('id_klienta', $klient->id_uzytkownika)->firstOrFail();

        $this->assertNull($klient->email);
        $this->assertSame('klient', $klient->rola);
        $this->assertSame('zatwierdzona', $rezerwacja->status, 'Termin uzgodniony telefonicznie nie wymaga zatwierdzania.');
        $this->assertSame($pracownik->id_uzytkownika, $rezerwacja->id_mechanika, 'Pracownik zapisuje wizytę na siebie.');
        Notification::assertNothingSent();
    }

    public function test_gdy_termin_sie_zajal_nie_powstaje_konto_ani_pojazd(): void
    {
        $pracownik = $this->mechanik();
        $usluga = $this->usluga(null, 60);
        $innyKlient = $this->uzytkownik('klient');
        $this->rezerwacja($innyKlient, $this->pojazd($innyKlient), [$usluga], '10:00', $pracownik->id_uzytkownika);

        $this->actingAs($pracownik)
            ->post(route('pracownik.reservations.nowa.store'), $this->daneNowegoKlienta($usluga->id_uslugi, '10:00'))
            ->assertSessionHasErrors('godzina');

        $this->assertDatabaseMissing('uzytkownicy', ['telefon' => '511222333']);
        $this->assertDatabaseMissing('pojazdy', ['numer_rejestracyjny' => 'LU 98765']);
    }

    public function test_nie_mozna_dodac_drugiego_klienta_z_tym_samym_telefonem(): void
    {
        $pracownik = $this->mechanik();
        $this->uzytkownik('klient', ['telefon' => '511222333']);

        $this->actingAs($pracownik)
            ->post(route('pracownik.reservations.nowa.store'), $this->daneNowegoKlienta($this->usluga(null)->id_uslugi, '10:00'))
            ->assertSessionHasErrors('telefon');
    }

    public function test_pracownik_nie_zapisze_uslugi_spoza_swoich_specjalizacji(): void
    {
        $pracownik = $this->mechanik();
        $klimatyzacja = $this->usluga($this->specjalizacja('Klimatyzacja'));

        $this->actingAs($pracownik)
            ->get(route('pracownik.reservations.nowa'))
            ->assertOk()
            ->assertSee('wymaga: Klimatyzacja');

        $this->actingAs($pracownik)
            ->post(route('pracownik.reservations.nowa.store'), $this->daneNowegoKlienta($klimatyzacja->id_uslugi, '10:00'))
            ->assertSessionHasErrors('uslugi');

        $this->assertDatabaseMissing('uzytkownicy', ['telefon' => '511222333']);
    }
}
