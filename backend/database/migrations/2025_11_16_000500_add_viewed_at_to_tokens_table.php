<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('tokens', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('signed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            if (Schema::hasColumn('tokens', 'viewed_at')) {
                $table->dropColumn('viewed_at');
            }
        });
    }
};
