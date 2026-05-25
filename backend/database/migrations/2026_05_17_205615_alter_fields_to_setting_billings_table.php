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
        Schema::table('setting_billings', function (Blueprint $table) {
            $table->string('sms_message')->nullable()->after('terms_and_conditions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_billings', function (Blueprint $table) {
            $table->dropColumn('sms_message');
        });
    }
};
