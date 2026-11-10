<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protokoly', function (Blueprint $table) {
            $table->id('id_protokolu');
            $table->foreignId('id_rezerwacji')->unique()
                ->constrained('rezerwacje', 'id_rezerwacji')->cascadeOnDelete();
            $table->unsignedInteger('przebieg')->nullable();
            $table->text('opis_prac');
            $table->text('zalecenia')->nullable();
            $table->date('data_nastepnej_wizyty')->nullable();
            $table->decimal('koszt_koncowy', 10, 2)->nullable();
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO protokoly (id_rezerwacji, przebieg, opis_prac, zalecenia, data_nastepnej_wizyty, koszt_koncowy, created_at, updated_at)
            SELECT id_rezerwacji, przebieg, opis_prac, zalecenia, data_nastepnej_wizyty, koszt_koncowy,
                   COALESCE(data_zakonczenia, updated_at), updated_at
            FROM rezerwacje
            WHERE opis_prac IS NOT NULL
        ");

        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropColumn(['przebieg', 'opis_prac', 'zalecenia', 'data_nastepnej_wizyty', 'koszt_koncowy', 'data_zakonczenia']);
        });

        if (DB::table('uslugi')->whereNull('id_specjalizacji')->exists()) {
            throw new RuntimeException('W tabeli uslugi są usługi bez specjalizacji. Przypisz im specjalizację w panelu administratora i uruchom migrację ponownie.');
        }
        Schema::table('uslugi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_specjalizacji')->nullable(false)->change();
        });

        DB::statement('ALTER TABLE godziny_pracy CHANGE id id_dnia BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        Schema::table('godziny_pracy', function (Blueprint $table) {
            $table->unique('dzien_tygodnia', 'godziny_pracy_dzien_tygodnia_unique');
        });

        Schema::table('pojazdy', function (Blueprint $table) {
            $table->unique('vin', 'pojazdy_vin_unique');
        });

        DB::statement('ALTER TABLE grafik_pracownikow ADD CONSTRAINT grafik_zmiana_check CHECK (godzina_do > godzina_od)');
        DB::statement('ALTER TABLE rezerwacje ADD CONSTRAINT rezerwacje_termin_check CHECK (termin_zakonczenia > termin_rozpoczecia)');
        DB::statement('ALTER TABLE uslugi ADD CONSTRAINT uslugi_cena_check CHECK (cena >= 0)');
        DB::statement('ALTER TABLE uslugi ADD CONSTRAINT uslugi_czas_check CHECK (czas_trwania > 0 AND czas_trwania % 30 = 0)');
        DB::statement('ALTER TABLE protokoly ADD CONSTRAINT protokoly_koszt_check CHECK (koszt_koncowy IS NULL OR koszt_koncowy >= 0)');
    }

    public function down(): void
    {
        foreach ([
            'protokoly' => 'protokoly_koszt_check',
            'uslugi' => 'uslugi_czas_check',
        ] as $tabela => $nazwa) {
            DB::statement("ALTER TABLE {$tabela} DROP CONSTRAINT {$nazwa}");
        }
        DB::statement('ALTER TABLE uslugi DROP CONSTRAINT uslugi_cena_check');
        DB::statement('ALTER TABLE rezerwacje DROP CONSTRAINT rezerwacje_termin_check');
        DB::statement('ALTER TABLE grafik_pracownikow DROP CONSTRAINT grafik_zmiana_check');

        Schema::table('pojazdy', function (Blueprint $table) {
            $table->dropUnique('pojazdy_vin_unique');
        });

        Schema::table('godziny_pracy', function (Blueprint $table) {
            $table->dropUnique('godziny_pracy_dzien_tygodnia_unique');
        });
        DB::statement('ALTER TABLE godziny_pracy CHANGE id_dnia id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        Schema::table('uslugi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_specjalizacji')->nullable()->change();
        });

        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->unsignedInteger('przebieg')->nullable();
            $table->text('opis_prac')->nullable();
            $table->text('zalecenia')->nullable();
            $table->date('data_nastepnej_wizyty')->nullable();
            $table->decimal('koszt_koncowy', 10, 2)->nullable();
            $table->timestamp('data_zakonczenia')->nullable();
        });

        DB::statement('
            UPDATE rezerwacje r JOIN protokoly p ON p.id_rezerwacji = r.id_rezerwacji
            SET r.przebieg = p.przebieg, r.opis_prac = p.opis_prac, r.zalecenia = p.zalecenia,
                r.data_nastepnej_wizyty = p.data_nastepnej_wizyty, r.koszt_koncowy = p.koszt_koncowy,
                r.data_zakonczenia = p.created_at
        ');

        Schema::dropIfExists('protokoly');
    }
};
