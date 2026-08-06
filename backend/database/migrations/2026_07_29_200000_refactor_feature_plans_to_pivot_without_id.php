<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('feature_plans') || !Schema::hasColumn('feature_plans', 'id')) {
            return;
        }

        DB::table('feature_plans')
            ->whereNull('feature_id')
            ->orWhereNull('plan_id')
            ->delete();

        // Keep only one row per pair before adding composite key.
        DB::statement('DELETE fp1 FROM feature_plans fp1 INNER JOIN feature_plans fp2 ON fp1.feature_id = fp2.feature_id AND fp1.plan_id = fp2.plan_id AND fp1.id > fp2.id');

        $this->dropForeignKeysForColumns('feature_plans', ['feature_id', 'plan_id']);

        Schema::table('feature_plans', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        // Convert to pure pivot key and keep FK integrity.
        Schema::table('feature_plans', function (Blueprint $table) {
            $table->primary(['feature_id', 'plan_id']);
            $table->foreign('feature_id')->references('id')->on('features')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('feature_plans') || Schema::hasColumn('feature_plans', 'id')) {
            return;
        }

        $this->dropForeignKeysForColumns('feature_plans', ['feature_id', 'plan_id']);

        DB::statement('ALTER TABLE feature_plans DROP PRIMARY KEY');

        Schema::table('feature_plans', function (Blueprint $table) {
            $table->id();
            $table->foreign('feature_id')->references('id')->on('features')->nullOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });
    }

    private function dropForeignKeysForColumns(string $tableName, array $columns): void
    {
        $databaseName = DB::getDatabaseName();

        $placeholders = implode(',', array_fill(0, count($columns), '?'));

        $rows = DB::select(
            "SELECT DISTINCT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME IN ($placeholders)
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            array_merge([$databaseName, $tableName], $columns)
        );

        foreach ($rows as $row) {
            $constraintName = str_replace('`', '``', $row->CONSTRAINT_NAME);
            DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY `{$constraintName}`");
        }
    }
};
