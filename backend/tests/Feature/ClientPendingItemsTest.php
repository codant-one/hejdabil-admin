<?php

namespace Tests\Feature;

use App\Http\Controllers\ClientController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientPendingItemsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('invoice_id')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total')->default(0);
            $table->decimal('amount_discount')->default(0);
            $table->unsignedBigInteger('state_id');
            $table->softDeletes();
        });
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('agreement_id');
            $table->unsignedBigInteger('agreement_type_id')->nullable();
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->unsignedBigInteger('commission_id')->nullable();
            $table->unsignedBigInteger('vehicle_client_id')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('agreement_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agreement_id');
            $table->unsignedBigInteger('client_id');
        });
        Schema::create('vehicle_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
        });
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agreement_id');
            $table->string('signature_status');
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_pending_items_loads_the_latest_token_without_ambiguous_columns(): void
    {
        DB::table('clients')->insert(['id' => 2]);

        foreach ([1, 2, 3, 4] as $id) {
            DB::table('agreements')->insert([
                'id' => $id,
                'agreement_id' => 'AG-'.$id,
            ]);
            DB::table('agreement_clients')->insert([
                'agreement_id' => $id,
                'client_id' => 2,
            ]);
        }

        DB::table('tokens')->insert([
            ['id' => 1, 'agreement_id' => 1, 'signature_status' => 'signed'],
            ['id' => 2, 'agreement_id' => 1, 'signature_status' => 'sent'],
            ['id' => 3, 'agreement_id' => 2, 'signature_status' => 'sent'],
            ['id' => 4, 'agreement_id' => 2, 'signature_status' => 'signed'],
            ['id' => 5, 'agreement_id' => 3, 'signature_status' => 'cancelled'],
        ]);

        $response = (new ClientController)->pendingItems(2);
        $data = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data));
        $this->assertTrue($data['success']);
        $this->assertSame([], $data['data']['pending_invoices']);
        $this->assertSame([4, 1], array_column($data['data']['open_agreements'], 'id'));
        $this->assertNull($data['data']['open_agreements'][0]['token']);
        $this->assertSame([
            'id' => 2,
            'agreement_id' => 1,
            'signature_status' => 'sent',
        ], $data['data']['open_agreements'][1]['token']);
    }
}
