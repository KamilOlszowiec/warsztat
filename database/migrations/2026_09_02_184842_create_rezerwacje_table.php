<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_rezerwacje_table.php
    public function up(): void
    {
        Schema::create('rezerwacje', function (Blueprint $table) {
            $table->id('id_rezerwacji');
            $table->foreignId('id_klienta')->constrained('uzytkownicy', 'id_uzytkownika');
            $table->foreignId('id_pojazdu')->constrained('pojazdy', 'id_pojazdu');
            $table->foreignId('id_uslugi')->constrained('uslugi', 'id_uslugi');
            $table->foreignId('id_pracownika')->nullable()->constrained('uzytkownicy', 'id_uzytkownika');
            $table->dateTime('termin_rozpoczecia');
            $table->dateTime('termin_zakonczenia');
            $table->enum('status', ['oczekująca', 'zatwierdzona', 'odrzucona', 'anulowana', 'zakończona'])->default('oczekująca');
            $table->text('uwagi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rezerwacje');
    }
};
