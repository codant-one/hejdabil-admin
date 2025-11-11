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
        Schema::table('tokens', function (Blueprint $table) {
            // Aumentar precisión para minimizar error de redondeo en coordenadas
            $table->decimal('placement_x', 10, 4)->nullable()->change();
            $table->decimal('placement_y', 10, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            // Volver a la precisión anterior (8,2)
            $table->decimal('placement_x', 8, 2)->nullable()->change();
            $table->decimal('placement_y', 8, 2)->nullable()->change();
        });
    }
};


