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
        Schema::create('supplier_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('entity_id')->comment('ID of the related entity record');
            $table->string('entity_type')->comment('Type of the related entity, such as invoice, contract, or vehicle');
            $table->string('action_type')->comment('Action code that describes what happened');
            $table->string('title')->comment('Short title shown in the activity feed');
            $table->string('description')->comment('Detailed description shown in the activity feed');
            $table->string('icon')->comment('Icon name used to display the activity in the UI');
            $table->string('route')->nullable()->comment('Optional route name for linking to the related entity');
            $table->longText('metadata')->nullable()->comment('Optional extra data for the activity, usually stored as encoded JSON');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_activities');
    }
};
