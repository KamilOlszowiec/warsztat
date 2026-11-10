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
        Schema::create('uslugi', function (Blueprint $table) {
            $table->id('id_uslugi');
            $table->string('nazwa', 100);
            $table->text('opis')->nullable();
            $table->decimal('cena', 10, 2);
            $table->integer('czas_trwania'); // w minutach
            $table->boolean('czy_aktywna')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uslugi');
    }
};
