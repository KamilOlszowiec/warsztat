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
        Schema::create('godziny_pracy', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('dzien_tygodnia'); // 1 = poniedziałek ... 7 = niedziela
            $table->time('godzina_otwarcia')->nullable();
            $table->time('godzina_zamkniecia')->nullable();
            $table->boolean('czy_czynne')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('godziny_pracy');
    }
};
