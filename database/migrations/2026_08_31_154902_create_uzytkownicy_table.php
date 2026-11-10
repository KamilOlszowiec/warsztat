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
        Schema::create('uzytkownicy', function (Blueprint $table) {
            $table->id('id_uzytkownika');
            $table->string('imie', 50);
            $table->string('nazwisko', 50);
            $table->string('email', 100)->unique();
            $table->string('password'); 
            $table->string('telefon', 15)->nullable();
            $table->enum('rola', ['klient', 'pracownik', 'administrator'])->default('klient');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzytkownicy');
    }
};
