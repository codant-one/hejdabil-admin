<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return !empty($result);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = 'supplier_invoices';
        $supportIndex = 'supplier_invoices_supplier_id_fk_support_idx';

        // MySQL requires an index that starts with supplier_id for the FK.
        if (!$this->hasIndex($tableName, $supportIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($supportIndex) {
                $table->index('supplier_id', $supportIndex);
            });
        }

        if ($this->hasIndex($tableName, 'unique_supplier_period')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique('unique_supplier_period');
            });
        }

        if (!$this->hasIndex($tableName, 'unique_supplier_period_credit')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(['supplier_id', 'billing_period', 'is_credit'], 'unique_supplier_period_credit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'supplier_invoices';
        $supportIndex = 'supplier_invoices_supplier_id_fk_support_idx';

        if ($this->hasIndex($tableName, 'unique_supplier_period_credit')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique('unique_supplier_period_credit');
            });
        }

        if (!$this->hasIndex($tableName, 'unique_supplier_period')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(['supplier_id', 'billing_period'], 'unique_supplier_period');
            });
        }

        if ($this->hasIndex($tableName, $supportIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($supportIndex) {
                $table->dropIndex($supportIndex);
            });
        }
    }
};
