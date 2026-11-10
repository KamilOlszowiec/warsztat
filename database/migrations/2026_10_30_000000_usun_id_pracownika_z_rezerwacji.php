<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropForeign(['id_pracownika']);
            $table->dropColumn('id_pracownika');
        });
    }

    public function down(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->foreignId('id_pracownika')->nullable()->after('id_mechanika')
                ->constrained('uzytkownicy', 'id_uzytkownika');
        });
    }
};
