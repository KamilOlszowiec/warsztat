<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Protokół z wizyty uzupełniany przez mechanika przy jej zakończeniu.
     * Kolumny są puste dla wizyt, które jeszcze się nie zakończyły.
     */
    public function up(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->unsignedInteger('przebieg')->nullable()->after('powod_odrzucenia');
            $table->text('opis_prac')->nullable()->after('przebieg');
            $table->text('zalecenia')->nullable()->after('opis_prac');
            $table->date('data_nastepnej_wizyty')->nullable()->after('zalecenia');
            $table->decimal('koszt_koncowy', 10, 2)->nullable()->after('data_nastepnej_wizyty');
            $table->timestamp('data_zakonczenia')->nullable()->after('koszt_koncowy');
        });
    }

    public function down(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropColumn(['przebieg', 'opis_prac', 'zalecenia', 'data_nastepnej_wizyty', 'koszt_koncowy', 'data_zakonczenia']);
        });
    }
};
