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
        Schema::table('uslugi', function (Blueprint $table) {
            $table->unsignedTinyInteger('maks_jednoczesnie')->default(1)->after('czas_trwania');
        });
    }

    public function down(): void
    {
        Schema::table('uslugi', function (Blueprint $table) {
            $table->dropColumn('maks_jednoczesnie');
        });
    }
};
