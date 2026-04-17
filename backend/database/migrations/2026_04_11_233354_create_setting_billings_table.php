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
        Schema::create('setting_billings', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->default(1);
            $table->integer('due_dates');
            $table->longText('terms_and_conditions');
            $table->tinyInteger('send_reminder')->default(1);
            $table->tinyInteger('send_notifications')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_billings');
    }
};
