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
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->text('powod_odrzucenia')->nullable()->after('uwagi');
        });
    }

    public function down(): void
    {
        Schema::table('rezerwacje', function (Blueprint $table) {
            $table->dropColumn('powod_odrzucenia');
        });
    }
};
