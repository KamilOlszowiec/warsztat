<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->index(['id_mechanika', 'termin_rozpoczecia'], 'rezerwacje_mechanik_termin_index');
            $table->index(['status', 'termin_rozpoczecia'], 'rezerwacje_status_termin_index');
            $table->index(['id_pojazdu', 'termin_rozpoczecia'], 'rezerwacje_pojazd_termin_index');
        });

        Schema::table('uzytkownicy', function (Blueprint $table) {
            $table->index('rola', 'uzytkownicy_rola_index');
        });

        Schema::table('pojazdy', function (Blueprint $table) {
            $table->unique('numer_rejestracyjny', 'pojazdy_numer_rejestracyjny_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pojazdy', function (Blueprint $table) {
            $table->dropUnique('pojazdy_numer_rejestracyjny_unique');
        });

        Schema::table('uzytkownicy', function (Blueprint $table) {
            $table->dropIndex('uzytkownicy_rola_index');
        });

        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropIndex('rezerwacje_mechanik_termin_index');
            $table->dropIndex('rezerwacje_status_termin_index');
            $table->dropIndex('rezerwacje_pojazd_termin_index');
        });
    }
};
