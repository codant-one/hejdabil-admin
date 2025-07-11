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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('agreement_type_id');
            $table->unsignedBigInteger('vehicle_client_id')->nullable();
            $table->unsignedBigInteger('vehicle_interchange_id')->nullable();
            $table->unsignedBigInteger('guaranty_id')->nullable();
            $table->unsignedBigInteger('guaranty_type_id')->nullable();
            $table->unsignedBigInteger('insurance_company_id')->nullable();
            $table->unsignedBigInteger('insurance_type_id')->nullable();
            $table->string("insurance_agent")->nullable()->comment("Insurance agent");
            $table->decimal("price", 10, 2)->nullable()->comment("Agreement price");
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger("iva_id")->nullable()->comment("IVA (0: VMB, 1: TINA)");
            $table->decimal("iva_amount", 10, 2)->nullable()->comment("IVA amount");
            $table->decimal("registration_fee", 10, 2)->nullable()->comment("Registration fee");
            $table->unsignedBigInteger('payment_type_id')->nullable();
            $table->decimal("down_payment_percentage", 2, 2)->nullable()->comment("Down payment percentage (Handpenning)");
            $table->decimal("payment_received", 10, 2)->nullable()->comment("Total cash / down payment (Summa kontant / handpenning)");
            $table->string("payment_method_forcash")->nullable()->comment("Payment method for cash / down payment (Betalsätt för kontant / handpenning)"); 
            $table->decimal("installment_amount", 10, 2)->nullable()->comment("Installment amount - credit amount/leasing (Avbetalningsbelopp (kreditbelopp/leasing))");
            $table->tinyInteger('installment_contract_upon_delivery')->nullable()->default(0)->comment("Installment contract is established upon delivery? - 0: No, 1: Yes (Avbetalningskontrakt upprättas vid leverans)"); (boolean)
            $table->string("payment_description")->nullable()->comment("Payment description");
            $table->longText("terms_other_conditions")->nullable()->comment("Terms other conditions");
            $table->longText("terms_other_information")->nullable()->comment("Terms other information");

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('agreement_type_id')->references('id')->on('agreement_types')->onDelete('cascade');
            $table->foreign('vehicle_client_id')->references('id')->on('vehicle_clients')->onDelete('cascade');
            $table->foreign('vehicle_interchange_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('guaranty_id')->references('id')->on('guaranties')->onDelete('cascade');
            $table->foreign('guaranty_type_id')->references('id')->on('guaranty_types')->onDelete('cascade');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
            $table->foreign('insurance_type_id')->references('id')->on('insurance_types')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->foreign('iva_id')->references('id')->on('ivas')->onDelete('cascade');
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
