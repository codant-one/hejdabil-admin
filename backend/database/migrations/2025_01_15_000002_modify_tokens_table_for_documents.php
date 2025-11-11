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
        Schema::table('tokens', function (Blueprint $table) {
            // Hacer agreement_id nullable y agregar campos para documentos
            $table->unsignedBigInteger('agreement_id')->nullable()->change();
            $table->unsignedBigInteger('document_id')->nullable()->after('agreement_id');
            $table->string('signable_type')->nullable()->after('document_id');
            $table->unsignedBigInteger('signable_id')->nullable()->after('signable_type');
            
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropColumn(['document_id', 'signable_type', 'signable_id']);
            $table->unsignedBigInteger('agreement_id')->nullable(false)->change();
        });
    }
};

