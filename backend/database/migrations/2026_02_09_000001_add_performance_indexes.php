<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add indexes to frequently queried columns for better performance
     */
    public function up(): void
    {
        // Vehicles table - most queried table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('state_id', 'idx_vehicles_state');
            $table->index('model_id', 'idx_vehicles_model');
            $table->index('year', 'idx_vehicles_year');
            $table->index('gearbox_id', 'idx_vehicles_gearbox');
            $table->index('supplier_id', 'idx_vehicles_supplier');
            $table->index('user_id', 'idx_vehicles_user');
            $table->index('car_body_id', 'idx_vehicles_carbody');
            $table->index('fuel_id', 'idx_vehicles_fuel');
            $table->index(['supplier_id', 'state_id'], 'idx_vehicles_supplier_state');
            $table->index('created_at', 'idx_vehicles_created');
        });

        // Agreements table
        Schema::table('agreements', function (Blueprint $table) {
            $table->index('supplier_id', 'idx_agreements_supplier');
            $table->index('agreement_type_id', 'idx_agreements_type');
            $table->index('user_id', 'idx_agreements_user');
            $table->index(['supplier_id', 'agreement_type_id'], 'idx_agreements_supplier_type');
            $table->index('created_at', 'idx_agreements_created');
        });

        // Billings table - has aggregates
        Schema::table('billings', function (Blueprint $table) {
            $table->index('supplier_id', 'idx_billings_supplier');
            $table->index('client_id', 'idx_billings_client');
            $table->index('state_id', 'idx_billings_state');
            $table->index('user_id', 'idx_billings_user');
            $table->index(['supplier_id', 'state_id'], 'idx_billings_supplier_state');
            $table->index('created_at', 'idx_billings_created');
        });

        // Clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->index('supplier_id', 'idx_clients_supplier');
            $table->index('user_id', 'idx_clients_user');
            $table->index('deleted_at', 'idx_clients_deleted');
            $table->index('created_at', 'idx_clients_created');
        });

        // Suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('boss_id', 'idx_suppliers_boss');
            $table->index('state_id', 'idx_suppliers_state');
            $table->index('user_id', 'idx_suppliers_user');
            $table->index('deleted_at', 'idx_suppliers_deleted');
            $table->index(['boss_id', 'deleted_at'], 'idx_suppliers_boss_deleted');
        });

        // Payouts table
        Schema::table('payouts', function (Blueprint $table) {
            $table->index('supplier_id', 'idx_payouts_supplier');
            $table->index('state_id', 'idx_payouts_state');
            $table->index('user_id', 'idx_payouts_user');
            $table->index(['supplier_id', 'state_id'], 'idx_payouts_supplier_state');
            $table->index('created_at', 'idx_payouts_created');
        });

        // Notes table
        Schema::table('notes', function (Blueprint $table) {
            $table->index('supplier_id', 'idx_notes_supplier');
            $table->index('user_id', 'idx_notes_user');
            $table->index('created_at', 'idx_notes_created');
        });

        // Documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->index('supplier_id', 'idx_documents_supplier');
            $table->index('user_id', 'idx_documents_user');
            $table->index('status', 'idx_documents_status');
            $table->index(['supplier_id', 'status'], 'idx_documents_supplier_status');
            $table->index('created_at', 'idx_documents_created');
        });

        // Models table (car_models) - the table is called 'models' not 'car_models'
        Schema::table('models', function (Blueprint $table) {
            $table->index('brand_id', 'idx_models_brand');
        });

        // Job queue table for faster processing
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['queue', 'reserved_at'], 'idx_jobs_queue_reserved');
            $table->index('available_at', 'idx_jobs_available');
        });

        // User details for frequent joins - user_id is already primary/foreign key, no need for additional index
        // Schema::table('user_details', function (Blueprint $table) {
        //     $table->index('user_id', 'idx_user_details_user'); // Already has foreign key
        // });

        // Commissions - no vehicle_id column, uses commission_id instead
        Schema::table('commissions', function (Blueprint $table) {
            $table->index('commission_id', 'idx_commissions_commission');
            $table->index('user_id', 'idx_commissions_user');
            $table->index('commission_type_id', 'idx_commissions_type');
        });

        // Vehicle clients - no agreement_id column
        Schema::table('vehicle_clients', function (Blueprint $table) {
            $table->index('vehicle_id', 'idx_vehicle_clients_vehicle');
            $table->index('client_id', 'idx_vehicle_clients_client');
        });

        // Agreement clients
        Schema::table('agreement_clients', function (Blueprint $table) {
            $table->index('agreement_id', 'idx_agreement_clients_agreement');
        });

        // Tokens for digital signatures
        Schema::table('tokens', function (Blueprint $table) {
            $table->index('agreement_id', 'idx_tokens_agreement');
            $table->index('document_id', 'idx_tokens_document');
            $table->index('signature_status', 'idx_tokens_status');
        });

        // Token histories - table is 'token_history' not 'token_histories'
        Schema::table('token_history', function (Blueprint $table) {
            $table->index('token_id', 'idx_token_history_token');
            $table->index('created_at', 'idx_token_history_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex('idx_vehicles_state');
            $table->dropIndex('idx_vehicles_model');
            $table->dropIndex('idx_vehicles_year');
            $table->dropIndex('idx_vehicles_gearbox');
            $table->dropIndex('idx_vehicles_supplier');
            $table->dropIndex('idx_vehicles_user');
            $table->dropIndex('idx_vehicles_carbody');
            $table->dropIndex('idx_vehicles_fuel');
            $table->dropIndex('idx_vehicles_supplier_state');
            $table->dropIndex('idx_vehicles_created');
        });

        // Agreements
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropIndex('idx_agreements_supplier');
            $table->dropIndex('idx_agreements_type');
            $table->dropIndex('idx_agreements_user');
            $table->dropIndex('idx_agreements_supplier_type');
            $table->dropIndex('idx_agreements_created');
        });

        // Billings
        Schema::table('billings', function (Blueprint $table) {
            $table->dropIndex('idx_billings_supplier');
            $table->dropIndex('idx_billings_client');
            $table->dropIndex('idx_billings_state');
            $table->dropIndex('idx_billings_user');
            $table->dropIndex('idx_billings_supplier_state');
            $table->dropIndex('idx_billings_created');
        });

        // Clients
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_supplier');
            $table->dropIndex('idx_clients_user');
            $table->dropIndex('idx_clients_deleted');
            $table->dropIndex('idx_clients_created');
        });

        // Suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('idx_suppliers_boss');
            $table->dropIndex('idx_suppliers_state');
            $table->dropIndex('idx_suppliers_user');
            $table->dropIndex('idx_suppliers_deleted');
            $table->dropIndex('idx_suppliers_boss_deleted');
        });

        // Payouts
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropIndex('idx_payouts_supplier');
            $table->dropIndex('idx_payouts_state');
            $table->dropIndex('idx_payouts_user');
            $table->dropIndex('idx_payouts_supplier_state');
            $table->dropIndex('idx_payouts_created');
        });

        // Notes
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('idx_notes_supplier');
            $table->dropIndex('idx_notes_user');
            $table->dropIndex('idx_notes_created');
        });

        // Documents
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_supplier');
            $table->dropIndex('idx_documents_user');
            $table->dropIndex('idx_documents_status');
            $table->dropIndex('idx_documents_supplier_status');
            $table->dropIndex('idx_documents_created');
        });

        // Models
        Schema::table('models', function (Blueprint $table) {
            $table->dropIndex('idx_models_brand');
        });

        // Jobs
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_queue_reserved');
            $table->dropIndex('idx_jobs_available');
        });

        // User details
        // Schema::table('user_details', function (Blueprint $table) {
        //     $table->dropIndex('idx_user_details_user');
        // });

        // Commissions
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropIndex('idx_commissions_commission');
            $table->dropIndex('idx_commissions_user');
            $table->dropIndex('idx_commissions_type');
        });

        // Vehicle clients
        Schema::table('vehicle_clients', function (Blueprint $table) {
            $table->dropIndex('idx_vehicle_clients_vehicle');
            $table->dropIndex('idx_vehicle_clients_client');
        });

        // Agreement clients
        Schema::table('agreement_clients', function (Blueprint $table) {
            $table->dropIndex('idx_agreement_clients_agreement');
        });

        // Tokens
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropIndex('idx_tokens_agreement');
            $table->dropIndex('idx_tokens_document');
            $table->dropIndex('idx_tokens_status');
        });

        // Token history
        Schema::table('token_history', function (Blueprint $table) {
            $table->dropIndex('idx_token_history_token');
            $table->dropIndex('idx_token_history_created');
        });
    }
};
