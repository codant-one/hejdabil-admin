<?php

namespace Tests\Feature;

use App\Http\Controllers\PlanController;
use App\Http\Requests\PlanRequest;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\SupplierActivity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlanActivityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        $user = new class {
            public int $id = 1;

            public function getRoleNames()
            {
                return collect(['Admin']);
            }
        };
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_month');
            $table->decimal('price_annual');
            $table->unsignedBigInteger('state_id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('feature_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('feature_id');
        });
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('supplier_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('entity_id');
            foreach (['entity_type', 'action_type', 'title', 'description', 'icon', 'route', 'metadata'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });

        Feature::create(['id' => 1, 'name' => 'Vehicles']);
        Feature::create(['id' => 2, 'name' => 'Invoices']);
        Feature::create(['id' => 3, 'name' => 'Reports']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function request(array|string $features): PlanRequest
    {
        return new PlanRequest([
            'name' => 'Test plan',
            'description' => 'Description',
            'price_month' => 10,
            'price_annual' => 100,
            'features' => $features,
        ]);
    }

    public function test_creation_records_saved_feature_ids_and_names_in_stable_order(): void
    {
        $response = (new PlanController)->store($this->request('[2,1,2]'));

        $this->assertSame(200, $response->getStatusCode());
        $metadata = json_decode(SupplierActivity::first()->metadata, true);
        $this->assertArrayNotHasKey('old_values', $metadata);
        $this->assertSame([
            ['id' => 1, 'name' => 'Vehicles'],
            ['id' => 2, 'name' => 'Invoices'],
        ], $metadata['new_values']['features']);
    }

    public function test_update_snapshots_features_before_and_after_sync(): void
    {
        $plan = Plan::createPlan($this->request([1, 2]));

        $response = (new PlanController)->update($this->request('[3,2]'), $plan->id);

        $this->assertSame(200, $response->getStatusCode());
        $metadata = json_decode(SupplierActivity::first()->metadata, true);
        $this->assertSame([
            ['id' => 1, 'name' => 'Vehicles'],
            ['id' => 2, 'name' => 'Invoices'],
        ], $metadata['old_values']['features']);
        $this->assertSame([
            ['id' => 2, 'name' => 'Invoices'],
            ['id' => 3, 'name' => 'Reports'],
        ], $metadata['new_values']['features']);

        Feature::find(2)->update(['name' => 'Renamed']);
        $this->assertSame($metadata, json_decode(SupplierActivity::first()->metadata, true));
    }

    public function test_removing_all_features_records_an_empty_new_snapshot(): void
    {
        $plan = Plan::createPlan($this->request([1]));

        $response = (new PlanController)->update($this->request([]), $plan->id);

        $this->assertSame(200, $response->getStatusCode());
        $metadata = json_decode(SupplierActivity::first()->metadata, true);
        $this->assertSame([['id' => 1, 'name' => 'Vehicles']], $metadata['old_values']['features']);
        $this->assertSame([], $metadata['new_values']['features']);
    }

    public function test_reordering_features_does_not_change_the_snapshot(): void
    {
        $plan = Plan::createPlan($this->request([1, 2]));

        (new PlanController)->update($this->request([2, 1]), $plan->id);

        $metadata = json_decode(SupplierActivity::first()->metadata, true);
        $this->assertSame($metadata['old_values']['features'], $metadata['new_values']['features']);
    }
}
