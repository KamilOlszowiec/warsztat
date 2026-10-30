<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adres e-mail staje się opcjonalny – klient zapisany telefonicznie
     * może nie podać e-maila. Unikalność zostaje: w MySQL/MariaDB
     * wiele wartości NULL nie narusza indeksu UNIQUE.
     */
    public function up(): void
    {
        Schema::table('uzytkownicy', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('uzytkownicy', function (Blueprint $table) {
            $table->string('email', 100)->nullable(false)->change();
        });
    }
};
