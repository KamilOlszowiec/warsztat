<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Godziny pracy warsztatu oraz dane demonstracyjne (konta wszystkich ról, usługi, grafik, wizyty).
     */
    public function run(): void
    {
        $this->call([
            GodzinyPracySeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
