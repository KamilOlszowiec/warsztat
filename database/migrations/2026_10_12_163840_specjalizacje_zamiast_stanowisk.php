<?php
// database/migrations/xxxx_xx_xx_xxxxxx_specjalizacje_zamiast_stanowisk.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Specjalizacje mechaników
        Schema::create('specjalizacje', function (Blueprint $table) {
            $table->id('id_specjalizacji');
            $table->string('nazwa', 50)->unique();
            $table->timestamps();
        });

        // 2. Specjalizacje przypisane pracownikom (wiele do wielu)
        Schema::create('pracownik_specjalizacja', function (Blueprint $table) {
            $table->foreignId('id_pracownika')
                ->constrained('uzytkownicy', 'id_uzytkownika')
                ->cascadeOnDelete();
            $table->foreignId('id_specjalizacji')
                ->constrained('specjalizacje', 'id_specjalizacji')
                ->cascadeOnDelete();
            $table->primary(['id_pracownika', 'id_specjalizacji']);
        });

        // 3. Usługa wymaga specjalizacji (zamiast typu stanowiska)
        Schema::table('uslugi', function (Blueprint $table) {
            $table->foreignId('id_specjalizacji')
                ->nullable()
                ->after('czas_trwania')
                ->constrained('specjalizacje', 'id_specjalizacji')
                ->restrictOnDelete();
        });

        Schema::table('uslugi', function (Blueprint $table) {
            $table->dropColumn('wymagany_typ_stanowiska');
        });

        // 4. Usunięcie stanowisk
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_stanowiska');
        });

        Schema::dropIfExists('stanowiska');
    }

    public function down(): void
    {
        Schema::create('stanowiska', function (Blueprint $table) {
            $table->id('id_stanowiska');
            $table->string('nazwa', 50);
            $table->enum('typ', ['podnosnik', 'kanal', 'plaskie', 'diagnostyczne']);
            $table->boolean('czy_aktywne')->default(true);
            $table->timestamps();
        });

        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->foreignId('id_stanowiska')
                ->nullable()
                ->after('id_pojazdu')
                ->constrained('stanowiska', 'id_stanowiska')
                ->nullOnDelete();
        });

        Schema::table('uslugi', function (Blueprint $table) {
            $table->enum('wymagany_typ_stanowiska', ['dowolne', 'podnosnik', 'kanal', 'plaskie', 'diagnostyczne'])
                ->default('dowolne')
                ->after('czas_trwania');
        });

        Schema::table('uslugi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_specjalizacji');
        });

        Schema::dropIfExists('pracownik_specjalizacja');
        Schema::dropIfExists('specjalizacje');
    }
};