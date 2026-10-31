<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Widoki w testach nie potrzebują zbudowanych stylów (public/build/manifest.json)
        $this->withoutVite();
    }

    /**
     * Zabezpieczenie: testy czyszczą bazę, więc mogą działać wyłącznie na bazie testowej.
     * Jeśli konfiguracja wskazuje inną bazę (np. przez pamięć podręczną konfiguracji),
     * testy zostają przerwane, zanim cokolwiek zostanie usunięte.
     */
    protected function setUpTraits()
    {
        $baza = config('database.connections.' . config('database.default') . '.database');

        if ($baza !== 'warsztat_test') {
            throw new RuntimeException(
                "Testy przerwane: skonfigurowana baza to „{$baza}”, a powinna być „warsztat_test”. "
                . 'Uruchom „php artisan config:clear” i spróbuj ponownie.'
            );
        }

        return parent::setUpTraits();
    }
}
