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
        Schema::table('payouts', function (Blueprint $table) {
            // Información básica del payout
            $table->string('payee_alias')->nullable()->after('payer_alias');
            $table->string('payee_ssn')->nullable()->after('payee_alias');
            $table->string('currency', 3)->default('SEK')->after('amount');
            $table->string('payout_type')->default('PAYOUT')->after('currency');
            
            // Fechas y referencias
            $table->string('instruction_date')->nullable()->after('payout_type');
            $table->string('payout_instruction_uuid')->nullable()->after('reference');
            $table->string('payer_payment_reference')->nullable()->after('payout_instruction_uuid');
            
            // Estado y mensajes
            $table->string('status')->default('CREATED')->after('payout_state_id'); // CREATED, DEBITED, PAID, ERROR, CANCELLED
            $table->text('message')->nullable()->after('status');
            $table->text('error_message')->nullable()->after('message');
            $table->string('error_code')->nullable()->after('error_message');
            
            // Información de callback
            $table->string('callback_url')->nullable()->after('error_code');
            $table->string('callback_identifier')->nullable()->after('callback_url');
            
            // Firma y certificado
            $table->text('signature')->nullable()->after('callback_identifier');
            $table->string('signing_certificate_serial_number')->nullable()->after('signature');
            
            // Payload completo para referencia
            $table->json('request_payload')->nullable()->after('signing_certificate_serial_number');
            $table->json('response_data')->nullable()->after('request_payload');
            
            // Location header de Swish (URL para consultar estado)
            $table->string('location_url')->nullable()->after('response_data');
            
            // Índices para búsquedas
            $table->index('status');
            $table->index('payout_instruction_uuid');
            $table->index('swish_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payout_instruction_uuid']);
            $table->dropIndex(['swish_id']);
            
            $table->dropColumn([
                'payee_alias',
                'payee_ssn',
                'currency',
                'payout_type',
                'instruction_date',
                'payout_instruction_uuid',
                'payer_payment_reference',
                'status',
                'message',
                'error_message',
                'error_code',
                'callback_url',
                'callback_identifier',
                'signature',
                'signing_certificate_serial_number',
                'request_payload',
                'response_data',
                'location_url'
            ]);
        });
    }
};
