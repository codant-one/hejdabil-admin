<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper to add index only if it doesn't exist
     */
    private function addIndexIfNotExists($table, $columns, $indexName): void
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (empty($indexes)) {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        }
    }

    /**
     * Helper to drop index only if it exists
     */
    private function dropIndexIfExists($table, $indexName): void
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (!empty($indexes)) {
            Schema::table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        }
    }

    /**
     * Run the migrations.
     * 
     * Add indexes to frequently queried columns for better performance
     */
    public function up(): void
    {
        // Vehicles table - most queried table
        $this->addIndexIfNotExists('vehicles', 'state_id', 'idx_vehicles_state');
        $this->addIndexIfNotExists('vehicles', 'model_id', 'idx_vehicles_model');
        $this->addIndexIfNotExists('vehicles', 'year', 'idx_vehicles_year');
        $this->addIndexIfNotExists('vehicles', 'gearbox_id', 'idx_vehicles_gearbox');
        $this->addIndexIfNotExists('vehicles', 'supplier_id', 'idx_vehicles_supplier');
        $this->addIndexIfNotExists('vehicles', 'user_id', 'idx_vehicles_user');
        $this->addIndexIfNotExists('vehicles', 'car_body_id', 'idx_vehicles_carbody');
        $this->addIndexIfNotExists('vehicles', 'fuel_id', 'idx_vehicles_fuel');
        $this->addIndexIfNotExists('vehicles', ['supplier_id', 'state_id'], 'idx_vehicles_supplier_state');
        $this->addIndexIfNotExists('vehicles', 'created_at', 'idx_vehicles_created');

        // Agreements table
        $this->addIndexIfNotExists('agreements', 'supplier_id', 'idx_agreements_supplier');
        $this->addIndexIfNotExists('agreements', 'agreement_type_id', 'idx_agreements_type');
        $this->addIndexIfNotExists('agreements', 'user_id', 'idx_agreements_user');
        $this->addIndexIfNotExists('agreements', ['supplier_id', 'agreement_type_id'], 'idx_agreements_supplier_type');
        $this->addIndexIfNotExists('agreements', 'created_at', 'idx_agreements_created');

        // Billings table
        $this->addIndexIfNotExists('billings', 'supplier_id', 'idx_billings_supplier');
        $this->addIndexIfNotExists('billings', 'client_id', 'idx_billings_client');
        $this->addIndexIfNotExists('billings', 'state_id', 'idx_billings_state');
        $this->addIndexIfNotExists('billings', 'user_id', 'idx_billings_user');
        $this->addIndexIfNotExists('billings', ['supplier_id', 'state_id'], 'idx_billings_supplier_state');
        $this->addIndexIfNotExists('billings', 'created_at', 'idx_billings_created');

        // Clients table
        $this->addIndexIfNotExists('clients', 'supplier_id', 'idx_clients_supplier');
        $this->addIndexIfNotExists('clients', 'user_id', 'idx_clients_user');
        $this->addIndexIfNotExists('clients', 'deleted_at', 'idx_clients_deleted');
        $this->addIndexIfNotExists('clients', 'created_at', 'idx_clients_created');

        // Suppliers table
        $this->addIndexIfNotExists('suppliers', 'boss_id', 'idx_suppliers_boss');
        $this->addIndexIfNotExists('suppliers', 'state_id', 'idx_suppliers_state');
        $this->addIndexIfNotExists('suppliers', 'user_id', 'idx_suppliers_user');
        $this->addIndexIfNotExists('suppliers', 'deleted_at', 'idx_suppliers_deleted');
        $this->addIndexIfNotExists('suppliers', ['boss_id', 'deleted_at'], 'idx_suppliers_boss_deleted');

        // Payouts table
        $this->addIndexIfNotExists('payouts', 'supplier_id', 'idx_payouts_supplier');
        $this->addIndexIfNotExists('payouts', 'payout_state_id', 'idx_payouts_state');
        $this->addIndexIfNotExists('payouts', 'user_id', 'idx_payouts_user');
        $this->addIndexIfNotExists('payouts', ['supplier_id', 'payout_state_id'], 'idx_payouts_supplier_state');
        $this->addIndexIfNotExists('payouts', 'created_at', 'idx_payouts_created');

        // Notes table
        $this->addIndexIfNotExists('notes', 'supplier_id', 'idx_notes_supplier');
        $this->addIndexIfNotExists('notes', 'user_id', 'idx_notes_user');
        $this->addIndexIfNotExists('notes', 'created_at', 'idx_notes_created');

        // Documents table
        $this->addIndexIfNotExists('documents', 'supplier_id', 'idx_documents_supplier');
        $this->addIndexIfNotExists('documents', 'user_id', 'idx_documents_user');
        $this->addIndexIfNotExists('documents', 'created_at', 'idx_documents_created');

        // Models table
        $this->addIndexIfNotExists('models', 'brand_id', 'idx_models_brand');

        // Job queue table
        $this->addIndexIfNotExists('jobs', ['queue', 'reserved_at'], 'idx_jobs_queue_reserved');
        $this->addIndexIfNotExists('jobs', 'available_at', 'idx_jobs_available');

        // Commissions
        $this->addIndexIfNotExists('commissions', 'commission_id', 'idx_commissions_commission');
        $this->addIndexIfNotExists('commissions', 'user_id', 'idx_commissions_user');
        $this->addIndexIfNotExists('commissions', 'commission_type_id', 'idx_commissions_type');

        // Vehicle clients
        $this->addIndexIfNotExists('vehicle_clients', 'vehicle_id', 'idx_vehicle_clients_vehicle');
        $this->addIndexIfNotExists('vehicle_clients', 'client_id', 'idx_vehicle_clients_client');

        // Agreement clients
        $this->addIndexIfNotExists('agreement_clients', 'agreement_id', 'idx_agreement_clients_agreement');

        // Tokens
        $this->addIndexIfNotExists('tokens', 'agreement_id', 'idx_tokens_agreement');
        $this->addIndexIfNotExists('tokens', 'document_id', 'idx_tokens_document');
        $this->addIndexIfNotExists('tokens', 'signature_status', 'idx_tokens_status');

        // Token history
        $this->addIndexIfNotExists('token_history', 'token_id', 'idx_token_history_token');
        $this->addIndexIfNotExists('token_history', 'created_at', 'idx_token_history_created');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vehicles
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_state');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_model');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_year');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_gearbox');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_supplier');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_user');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_carbody');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_fuel');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_supplier_state');
        $this->dropIndexIfExists('vehicles', 'idx_vehicles_created');

        // Agreements
        $this->dropIndexIfExists('agreements', 'idx_agreements_supplier');
        $this->dropIndexIfExists('agreements', 'idx_agreements_type');
        $this->dropIndexIfExists('agreements', 'idx_agreements_user');
        $this->dropIndexIfExists('agreements', 'idx_agreements_supplier_type');
        $this->dropIndexIfExists('agreements', 'idx_agreements_created');

        // Billings
        $this->dropIndexIfExists('billings', 'idx_billings_supplier');
        $this->dropIndexIfExists('billings', 'idx_billings_client');
        $this->dropIndexIfExists('billings', 'idx_billings_state');
        $this->dropIndexIfExists('billings', 'idx_billings_user');
        $this->dropIndexIfExists('billings', 'idx_billings_supplier_state');
        $this->dropIndexIfExists('billings', 'idx_billings_created');

        // Clients
        $this->dropIndexIfExists('clients', 'idx_clients_supplier');
        $this->dropIndexIfExists('clients', 'idx_clients_user');
        $this->dropIndexIfExists('clients', 'idx_clients_deleted');
        $this->dropIndexIfExists('clients', 'idx_clients_created');

        // Suppliers
        $this->dropIndexIfExists('suppliers', 'idx_suppliers_boss');
        $this->dropIndexIfExists('suppliers', 'idx_suppliers_state');
        $this->dropIndexIfExists('suppliers', 'idx_suppliers_user');
        $this->dropIndexIfExists('suppliers', 'idx_suppliers_deleted');
        $this->dropIndexIfExists('suppliers', 'idx_suppliers_boss_deleted');

        // Payouts
        $this->dropIndexIfExists('payouts', 'idx_payouts_supplier');
        $this->dropIndexIfExists('payouts', 'idx_payouts_state');
        $this->dropIndexIfExists('payouts', 'idx_payouts_user');
        $this->dropIndexIfExists('payouts', 'idx_payouts_supplier_state');
        $this->dropIndexIfExists('payouts', 'idx_payouts_created');

        // Notes
        $this->dropIndexIfExists('notes', 'idx_notes_supplier');
        $this->dropIndexIfExists('notes', 'idx_notes_user');
        $this->dropIndexIfExists('notes', 'idx_notes_created');

        // Documents
        $this->dropIndexIfExists('documents', 'idx_documents_supplier');
        $this->dropIndexIfExists('documents', 'idx_documents_user');
        $this->dropIndexIfExists('documents', 'idx_documents_created');

        // Models
        $this->dropIndexIfExists('models', 'idx_models_brand');

        // Jobs
        $this->dropIndexIfExists('jobs', 'idx_jobs_queue_reserved');
        $this->dropIndexIfExists('jobs', 'idx_jobs_available');

        // Commissions
        $this->dropIndexIfExists('commissions', 'idx_commissions_commission');
        $this->dropIndexIfExists('commissions', 'idx_commissions_user');
        $this->dropIndexIfExists('commissions', 'idx_commissions_type');

        // Vehicle clients
        $this->dropIndexIfExists('vehicle_clients', 'idx_vehicle_clients_vehicle');
        $this->dropIndexIfExists('vehicle_clients', 'idx_vehicle_clients_client');

        // Agreement clients
        $this->dropIndexIfExists('agreement_clients', 'idx_agreement_clients_agreement');

        // Tokens
        $this->dropIndexIfExists('tokens', 'idx_tokens_agreement');
        $this->dropIndexIfExists('tokens', 'idx_tokens_document');
        $this->dropIndexIfExists('tokens', 'idx_tokens_status');

        // Token history
        $this->dropIndexIfExists('token_history', 'idx_token_history_token');
        $this->dropIndexIfExists('token_history', 'idx_token_history_created');
    }
};
