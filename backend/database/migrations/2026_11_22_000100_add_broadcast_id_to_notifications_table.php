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
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('broadcast_id')->nullable()->after('supplier_id');

            $table->foreign('broadcast_id')->references('id')->on('admin_notifications')->onDelete('set null');

            // Idempotency: a user receives at most one notification per broadcast.
            // Existing notifications (broadcast_id NULL) are not affected.
            $table->unique(['broadcast_id', 'user_id'], 'notifications_broadcast_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['broadcast_id']);
            $table->dropUnique('notifications_broadcast_user_unique');
            $table->dropColumn('broadcast_id');
        });
    }
};
