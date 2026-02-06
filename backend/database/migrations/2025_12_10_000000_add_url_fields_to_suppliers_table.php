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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('crs_url')->nullable()->after('master_password');
            $table->string('key_url')->nullable()->after('crs_url');
            $table->string('pem_url')->nullable()->after('key_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['crs_url', 'key_url', 'pem_url']);
        });
    }
};
