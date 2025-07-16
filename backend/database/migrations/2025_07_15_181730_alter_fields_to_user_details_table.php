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
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('company')->nullable()->after('user_id');
            $table->string('organization_number')->nullable()->after('company');
            $table->string('street')->nullable()->after('address');
            $table->string('postal_code')->nullable()->after('street');
            $table->string('link')->nullable()->after('phone');
            $table->string('bank')->nullable()->after('link');
            $table->string('iban')->nullable()->after('bank');
            $table->string('account_number')->nullable()->after('iban');
            $table->string('iban_number')->nullable()->after('account_number');
            $table->string('bic')->nullable()->after('iban_number');
            $table->string('plus_spin')->nullable()->after('bic');
            $table->string('swish')->nullable()->after('plus_spin');
            $table->string('vat')->nullable()->after('swish');
            $table->string('logo')->nullable()->after('vat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('company');
            $table->dropColumn('organization_number');
            $table->dropColumn('street');
            $table->dropColumn('postal_code');
            $table->dropColumn('link');
            $table->dropColumn('bank');
            $table->dropColumn('iban');
            $table->dropColumn('account_number');
            $table->dropColumn('iban_number');
            $table->dropColumn('bic');
            $table->dropColumn('plus_spin');
            $table->dropColumn('swish');
            $table->dropColumn('vat');
            $table->dropColumn('logo');
        });
    }
};
