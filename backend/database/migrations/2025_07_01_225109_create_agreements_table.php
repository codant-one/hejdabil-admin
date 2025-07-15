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
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('agreement_type_id');
            $table->unsignedBigInteger('vehicle_client_id')->nullable();
            $table->unsignedBigInteger('vehicle_interchange_id')->nullable();
            $table->unsignedBigInteger('guaranty_id')->nullable();
            $table->unsignedBigInteger('guaranty_type_id')->nullable();
            $table->unsignedBigInteger('insurance_company_id')->nullable();
            $table->unsignedBigInteger('insurance_type_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('payment_type_id')->nullable();
            $table->unsignedBigInteger("iva_id")->nullable()->comment("IVA (0: VMB, 1: TINA)");
            $table->unsignedBigInteger('advance_id')->nullable();
            $table->unsignedBigInteger('agreement_id');

            $table->date("first_registration_date")->nullable()->comment("First registration date");
            $table->date("sale_date")->nullable()->comment("Sale date");
            $table->string("insurance_agent")->nullable()->comment("Insurance agent");
            $table->decimal("trade_price")->nullable()->comment("Trade price");
            $table->tinyInteger('residual_debt')->default(0)->comment("Residual debt (0: No, 1: Yes)");
            $table->decimal("residual_price")->nullable()->comment("Residual price");
            $table->decimal("fair_value")->nullable()->comment("Fair price");
            $table->string("remaining_paid_to")->nullable()->comment("Remaining paid to");
            $table->tinyInteger('redemption_offer')->default(0)->comment("Redemption offer (0: No, 1: Yes)");
            
            $table->decimal("price", 10, 2)->nullable()->comment("Agreement price");
            $table->decimal("iva_sale_amount", 10, 2)->nullable()->comment("IVA amount");
            $table->decimal("iva_sale_exclusive", 10, 2)->nullable()->comment("Sale without iva");
            $table->decimal("discount", 10, 2)->nullable()->comment("Discount");
            $table->decimal("registration_fee", 10, 2)->nullable()->comment("Registration fee");
            $table->decimal("total_sale", 10, 2)->nullable()->comment("Total sale");

            $table->decimal("middle_price", 10, 2)->nullable()->comment("Middle price");

            $table->decimal("payment_received", 10, 2)->nullable()->comment("Total cash / down payment (Summa kontant / handpenning)");
            $table->string("payment_method_forcash")->nullable()->comment("Payment method for cash / down payment (Betalsätt för kontant / handpenning)"); 
            $table->decimal("installment_amount", 10, 2)->nullable()->comment("Installment amount - credit amount/leasing (Avbetalningsbelopp (kreditbelopp/leasing))");
            $table->tinyInteger('installment_contract_upon_delivery')->nullable()->default(0)->comment("Installment contract is established upon delivery? - 0: No, 1: Yes (Avbetalningskontrakt upprättas vid leverans)"); (boolean)
            $table->string("payment_description")->nullable()->comment("Payment description");
            $table->longText("terms_other_conditions")->nullable()->comment("Terms other conditions");
            $table->longText("terms_other_information")->nullable()->comment("Terms other information");

            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
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
            $table->foreign('advance_id')->references('id')->on('advances')->onDelete('cascade');
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
