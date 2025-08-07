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
        Schema::table('agreements', function (Blueprint $table) {
            if (Schema::hasColumn('agreements', 'guaranty_id')) {
                $table->dropForeign(['guaranty_id']);
                $table->dropColumn('guaranty_id');
            }

            if(Schema::hasColumn('agreements', 'insurance_company_id')) {
                $table->dropForeign(['insurance_company_id']);
                $table->dropColumn('insurance_company_id');
            }
            $table->tinyInteger('guaranty')->default(0)->after('installment_contract_upon_delivery')->comment("There is a guaranty? (0: No, 1: Yes)");
            $table->string('guaranty_description')->nullable()->after('guaranty');
            $table->tinyInteger('insurance_company')->default(0)->after('guaranty_description')->comment("There is a insurance company? (0: No, 1: Yes)");
            $table->string('insurance_company_description')->nullable()->after('insurance_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('guaranty');
            $table->dropColumn('guaranty_description');
            $table->dropColumn('insurance_company');
            $table->dropColumn('insurance_company_description');
            $table->unsignedBigInteger('guaranty_id')->nullable()->after('vehicle_interchange_id');
            $table->foreign('guaranty_id')->references('id')->on('guaranties')->onDelete('cascade');
            $table->unsignedBigInteger('insurance_company_id')->nullable()->after('guaranty_type_id');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
        });
    }
};
