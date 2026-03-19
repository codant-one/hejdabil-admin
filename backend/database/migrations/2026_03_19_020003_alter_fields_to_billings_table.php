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
        Schema::table('billings', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_id')->nullable()->after('state_id');
            $table->tinyInteger('is_credit')->default(0)->after('is_sent');

            $table->foreign('credit_id')->references('id')->on('billings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->dropColumn('credit_id');
            $table->dropColumn('is_credit');
        });
    }
};
