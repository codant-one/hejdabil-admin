<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Agreement;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Agreement::class)->constrained()->cascadeOnDelete();
            $table->string('signature_status')->default('pending');
            $table->string('signing_token')->unique();
            $table->timestamp('token_expires_at')->nullable();
            //Despues de firmado
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_image_path')->nullable(); // Ruta a la imagen de la firma
            $table->string('signed_pdf_path')->nullable(); // Ruta al PDF final
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
