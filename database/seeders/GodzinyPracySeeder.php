<?php
// database/seeders/GodzinyPracySeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GodzinyPracySeeder extends Seeder
{
    public function run(): void
    {
        for ($dzien = 1; $dzien <= 7; $dzien++) {
            DB::table('godziny_pracy')->insert([
                'dzien_tygodnia' => $dzien,
                'godzina_otwarcia' => '08:00',
                'godzina_zamkniecia' => '16:00',
                'czy_czynne' => $dzien <= 5, // pon-pt czynne, sob-nd domyślnie zamknięte
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}