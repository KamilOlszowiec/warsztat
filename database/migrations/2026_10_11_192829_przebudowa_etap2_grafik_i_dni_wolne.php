<?php
// database/migrations/xxxx_xx_xx_xxxxxx_przebudowa_etap2_grafik_i_dni_wolne.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grafik: jeden wiersz = jedna zmiana pracownika w danym dniu
        Schema::create('grafik_pracownikow', function (Blueprint $table) {
            $table->id('id_zmiany');
            $table->foreignId('id_pracownika')
                ->constrained('uzytkownicy', 'id_uzytkownika')
                ->cascadeOnDelete();
            $table->date('data');
            $table->time('godzina_od');
            $table->time('godzina_do');
            $table->timestamps();
            $table->unique(['id_pracownika', 'data']);
        });

        // Dni, w których warsztat jest zamknięty (święta, inwentaryzacja itp.)
        Schema::create('dni_wolne_warsztatu', function (Blueprint $table) {
            $table->id('id_dnia_wolnego');
            $table->date('data')->unique();
            $table->string('opis', 100)->nullable();
            $table->timestamps();
        });

        // Mechanik wykonujący wizytę
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->foreignId('id_mechanika')
                ->nullable()
                ->after('id_stanowiska')
                ->constrained('uzytkownicy', 'id_uzytkownika')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_mechanika');
        });

        Schema::dropIfExists('dni_wolne_warsztatu');
        Schema::dropIfExists('grafik_pracownikow');
    }
};