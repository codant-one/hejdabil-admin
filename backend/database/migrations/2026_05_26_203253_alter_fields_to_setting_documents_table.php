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
        Schema::table('setting_documents', function (Blueprint $table) {
            $table->integer('due_dates')->default(5)->after('sms_message');
            $table->tinyInteger('send_reminder')->default(1)->after('due_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_documents', function (Blueprint $table) {
            $table->dropColumn('due_dates');
            $table->dropColumn('send_reminder');
        });
    }
};
