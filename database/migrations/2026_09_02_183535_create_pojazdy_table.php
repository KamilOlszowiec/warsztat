<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pojazdy', function (Blueprint $table) {
            $table->id('id_pojazdu');
            $table->foreignId('id_uzytkownika')->constrained('uzytkownicy', 'id_uzytkownika')->cascadeOnDelete();
            $table->string('marka', 50);
            $table->string('model', 50);
            $table->year('rok_produkcji')->nullable();
            $table->string('numer_rejestracyjny', 15);
            $table->string('vin', 17)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pojazdy');
    }
};
