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
            $table->tinyInteger('is_subscription_active')->default(1)->after('is_yearly');
            $table->date("cancellation_date")->nullable()->comment("Cancellation date")->after('next_billing_date');
            $table->date("grace_end_date")->nullable()->comment("Grace end date")->after('cancellation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('cancellation_date');
            $table->dropColumn('grace_end_date');
            $table->dropColumn('is_subscription_active');
        });
    }
};
