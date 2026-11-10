<?php
// database/migrations/xxxx_xx_xx_xxxxxx_przebudowa_etap1_stanowiska_i_wiele_uslug.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stanowiska', function (Blueprint $table) {
            $table->id('id_stanowiska');
            $table->string('nazwa', 50);
            $table->enum('typ', ['podnosnik', 'kanal', 'plaskie', 'diagnostyczne']);
            $table->boolean('czy_aktywne')->default(true);
            $table->timestamps();
        });

        Schema::table('uslugi', function (Blueprint $table) {
            $table->enum('wymagany_typ_stanowiska', ['dowolne', 'podnosnik', 'kanal', 'plaskie', 'diagnostyczne'])
                ->default('dowolne')
                ->after('czas_trwania');
        });

        Schema::table('uslugi', function (Blueprint $table) {
            $table->dropColumn('maks_jednoczesnie');
        });

        Schema::create('rezerwacja_usluga', function (Blueprint $table) {
            $table->foreignId('id_rezerwacji')
                ->constrained('rezerwacje', 'id_rezerwacji')
                ->cascadeOnDelete();
            $table->foreignId('id_uslugi')
                ->constrained('uslugi', 'id_uslugi')
                ->restrictOnDelete();
            $table->decimal('cena', 10, 2);
            $table->unsignedSmallInteger('czas_trwania');
            $table->primary(['id_rezerwacji', 'id_uslugi']);
        });

        DB::statement('
            INSERT INTO rezerwacja_usluga (id_rezerwacji, id_uslugi, cena, czas_trwania)
            SELECT r.id_rezerwacji, r.id_uslugi, u.cena, u.czas_trwania
            FROM rezerwacje r
            JOIN uslugi u ON u.id_uslugi = r.id_uslugi
        ');

        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_uslugi');
        });

        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->foreignId('id_stanowiska')
                ->nullable()
                ->after('id_pojazdu')
                ->constrained('stanowiska', 'id_stanowiska')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_stanowiska');
        });

        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->foreignId('id_uslugi')
                ->nullable()
                ->after('id_pojazdu')
                ->constrained('uslugi', 'id_uslugi');
        });

        DB::statement('
            UPDATE rezerwacje r
            SET id_uslugi = (
                SELECT MIN(ru.id_uslugi)
                FROM rezerwacja_usluga ru
                WHERE ru.id_rezerwacji = r.id_rezerwacji
            )
        ');

        Schema::dropIfExists('rezerwacja_usluga');

        Schema::table('uslugi', function (Blueprint $table) {
            $table->unsignedTinyInteger('maks_jednoczesnie')->default(1)->after('czas_trwania');
        });

        Schema::table('uslugi', function (Blueprint $table) {
            $table->dropColumn('wymagany_typ_stanowiska');
        });

        Schema::dropIfExists('stanowiska');
    }
};