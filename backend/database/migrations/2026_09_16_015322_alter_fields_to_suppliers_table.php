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
            $table->string("code")->nullable()->after('pem_url');
            $table->date("deletion_requested_at")->nullable()->comment("Deletion requested date")->after('code');
            $table->date("deletion_scheduled_at")->nullable()->comment("Deletion scheduled date")->after('deletion_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->dropColumn('deletion_requested_at');
            $table->dropColumn('deletion_scheduled_at');
        });
    }
};
