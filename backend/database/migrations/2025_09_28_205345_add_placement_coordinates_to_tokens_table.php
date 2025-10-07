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
            $table->decimal('placement_x', 8, 2)->nullable()->after('signing_token');
            $table->decimal('placement_y', 8, 2)->nullable()->after('placement_x');
            $table->integer('placement_page')->default(1)->after('placement_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('placement_x');
            $table->dropColumn('placement_y');
            $table->dropColumn('placement_page');
        });
    }
};
