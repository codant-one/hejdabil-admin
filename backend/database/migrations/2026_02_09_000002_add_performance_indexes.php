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
        // Índices para la tabla vehicles (consultas más frecuentes)
        if (!$this->indexExists('vehicles', 'idx_vehicles_state_supplier')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->index(['state_id', 'supplier_id'], 'idx_vehicles_state_supplier');
            });
        }
        if (!$this->indexExists('vehicles', 'idx_vehicles_model')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->index('model_id', 'idx_vehicles_model');
            });
        }
        if (!$this->indexExists('vehicles', 'idx_vehicles_year')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->index('year', 'idx_vehicles_year');
            });
        }
        if (!$this->indexExists('vehicles', 'idx_vehicles_gearbox')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->index('gearbox_id', 'idx_vehicles_gearbox');
            });
        }
        if (!$this->indexExists('vehicles', 'idx_vehicles_created')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->index('created_at', 'idx_vehicles_created');
            });
        }

        // Índices para la tabla agreements
        if (!$this->indexExists('agreements', 'idx_agreements_supplier_type')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->index(['supplier_id', 'agreement_type_id'], 'idx_agreements_supplier_type');
            });
        }
        if (!$this->indexExists('agreements', 'idx_agreements_created')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->index('created_at', 'idx_agreements_created');
            });
        }

        // Índices para la tabla billings
        if (!$this->indexExists('billings', 'idx_billings_client_state')) {
            Schema::table('billings', function (Blueprint $table) {
                $table->index(['client_id', 'state_id'], 'idx_billings_client_state');
            });
        }
        if (!$this->indexExists('billings', 'idx_billings_created')) {
            Schema::table('billings', function (Blueprint $table) {
                $table->index('created_at', 'idx_billings_created');
            });
        }

        // Índices para la tabla clients
        if (!$this->indexExists('clients', 'idx_clients_supplier')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('supplier_id', 'idx_clients_supplier');
            });
        }
        if (!$this->indexExists('clients', 'idx_clients_created')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('created_at', 'idx_clients_created');
            });
        }

        // Índices para la tabla suppliers
        if (!$this->indexExists('suppliers', 'idx_suppliers_boss')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->index('boss_id', 'idx_suppliers_boss');
            });
        }
        if (!$this->indexExists('suppliers', 'idx_suppliers_state')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->index('state_id', 'idx_suppliers_state');
            });
        }
        if (!$this->indexExists('suppliers', 'idx_suppliers_deleted')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->index('deleted_at', 'idx_suppliers_deleted');
            });
        }

        // Índices para la tabla users
        if (!$this->indexExists('users', 'idx_users_deleted')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('deleted_at', 'idx_users_deleted');
            });
        }

        // Índices para la tabla payouts
        if (!$this->indexExists('payouts', 'idx_payouts_supplier_state')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index(['supplier_id', 'payout_state_id'], 'idx_payouts_supplier_state');
            });
        }
        if (!$this->indexExists('payouts', 'idx_payouts_created')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index('created_at', 'idx_payouts_created');
            });
        }

        // Índices para la tabla notifications
        if (!$this->indexExists('notifications', 'idx_notifications_user_read')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['user_id', 'read'], 'idx_notifications_user_read');
            });
        }
        if (!$this->indexExists('notifications', 'idx_notifications_created')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index('created_at', 'idx_notifications_created');
            });
        }

        // Índices para la tabla vehicle_tasks
        if (!$this->indexExists('vehicle_tasks', 'idx_vehicle_tasks_vehicle')) {
            Schema::table('vehicle_tasks', function (Blueprint $table) {
                $table->index('vehicle_id', 'idx_vehicle_tasks_vehicle');
            });
        }
        if (!$this->indexExists('vehicle_tasks', 'idx_vehicle_tasks_user')) {
            Schema::table('vehicle_tasks', function (Blueprint $table) {
                $table->index('user_id', 'idx_vehicle_tasks_user');
            });
        }

        // Índices para la tabla vehicle_documents
        if (!$this->indexExists('vehicle_documents', 'idx_vehicle_documents_vehicle')) {
            Schema::table('vehicle_documents', function (Blueprint $table) {
                $table->index('vehicle_id', 'idx_vehicle_documents_vehicle');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $index]
        );
        
        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex('idx_vehicles_state_supplier');
            $table->dropIndex('idx_vehicles_model');
            $table->dropIndex('idx_vehicles_year');
            $table->dropIndex('idx_vehicles_gearbox');
            $table->dropIndex('idx_vehicles_created');
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->dropIndex('idx_agreements_supplier_type');
            $table->dropIndex('idx_agreements_created');
        });

        Schema::table('billings', function (Blueprint $table) {
            $table->dropIndex('idx_billings_client_state');
            $table->dropIndex('idx_billings_created');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_supplier');
            $table->dropIndex('idx_clients_created');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('idx_suppliers_boss');
            $table->dropIndex('idx_suppliers_state');
            $table->dropIndex('idx_suppliers_deleted');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_deleted');
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->dropIndex('idx_payouts_supplier_state');
            $table->dropIndex('idx_payouts_created');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
            $table->dropIndex('idx_notifications_created');
        });

        Schema::table('vehicle_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_vehicle_tasks_vehicle');
            $table->dropIndex('idx_vehicle_tasks_user');
        });

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->dropIndex('idx_vehicle_documents_vehicle');
        });
    }
};
