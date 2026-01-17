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
        Schema::create('token_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('tokens')->cascadeOnDelete();
            $table->string('event_type')->comment('reviewed, delivered, delivery_issues, signed, failed');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable(); 
            $table->string('user_agent')->nullable(); 
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Index for quick searches by token and event type
            $table->index(['token_id', 'event_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_history');
    }
};
