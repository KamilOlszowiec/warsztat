<?php
// database/seeders/StanowiskoSeeder.php

namespace Database\Seeders;

use App\Models\Stanowisko;
use Illuminate\Database\Seeder;

class StanowiskoSeeder extends Seeder
{
    public function run(): void
    {
        $stanowiska = [
            ['nazwa' => 'Podnośnik 1', 'typ' => 'podnosnik'],
            ['nazwa' => 'Podnośnik 2', 'typ' => 'podnosnik'],
            ['nazwa' => 'Kanał',       'typ' => 'kanal'],
            ['nazwa' => 'Stanowisko płaskie', 'typ' => 'plaskie'],
        ];

        foreach ($stanowiska as $s) {
            Stanowisko::firstOrCreate(['nazwa' => $s['nazwa']], $s + ['czy_aktywne' => true]);
        }
    }
}