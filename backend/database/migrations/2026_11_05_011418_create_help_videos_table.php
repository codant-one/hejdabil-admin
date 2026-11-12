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
        Schema::create('help_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('help_category_id');
            $table->unsignedBigInteger('state_id')->default(2);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url');
            $table->string('provider', 20)->default('youtube');
            $table->string('thumbnail_url', 500)->nullable();
            $table->timestamps();

            $table->foreign('help_category_id')->references('id')->on('help_categories')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_videos');
    }
};
