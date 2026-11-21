<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckUserInactivity;
use App\Http\Middleware\EnforceSubscriptionReadOnly;
use App\Http\Middleware\VerifyJwt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleEditingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');

        foreach (['roles', 'permissions'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('supplier_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type');
            $table->string('action_type');
            $table->string('title');
            $table->string('description');
            $table->string('icon');
            $table->string('route')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware([
            VerifyJwt::class,
            CheckUserInactivity::class,
            EnforceSubscriptionReadOnly::class,
            ThrottleRequests::class,
        ]);

        $actor = new User();
        $actor->id = 99;
        $actor->setRelation('roles', collect([new Role(['name' => 'Admin', 'guard_name' => 'api'])]));
        $this->actingAs($actor, 'api');
    }

    private function makeRole(): Role
    {
        $permission = Permission::create(['name' => 'view clients', 'guard_name' => 'api']);
        $role = Role::create(['name' => 'Sales', 'guard_name' => 'api']);
        $role->givePermissionTo($permission);

        return $role;
    }

    public function test_editor_can_load_existing_permissions_and_update_without_changing_the_name(): void
    {
        Gate::before(fn ($user, $ability) => $ability === 'edit roles');
        $role = $this->makeRole();
        Permission::create(['name' => 'create clients', 'guard_name' => 'api']);

        $this->getJson("/api/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('data.role.name', 'Sales')
            ->assertJsonPath('data.role.permissions.0.name', 'view clients');

        $this->putJson("/api/roles/{$role->id}", [
            'name' => 'Sales',
            'permissions' => ['create clients'],
        ])->assertOk();

        $this->getJson("/api/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('data.role.name', 'Sales')
            ->assertJsonCount(1, 'data.role.permissions')
            ->assertJsonPath('data.role.permissions.0.name', 'create clients');
    }

    public function test_missing_role_returns_an_explicit_not_found_response(): void
    {
        Gate::before(fn ($user, $ability) => $ability === 'edit roles');

        $this->getJson('/api/roles/999')
            ->assertNotFound()
            ->assertJsonPath('feedback', 'not_found')
            ->assertJsonPath('message', 'Rollen hittades inte');
    }

    public function test_view_permission_can_load_role_details_but_cannot_update_them(): void
    {
        Gate::before(fn ($user, $ability) => $ability === 'view roles');
        $role = $this->makeRole();

        $this->getJson("/api/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('data.role.name', 'Sales')
            ->assertJsonPath('data.role.permissions.0.name', 'view clients');
        $this->putJson("/api/roles/{$role->id}", [
            'name' => 'Changed',
            'permissions' => ['view clients'],
        ])->assertForbidden();

        $this->assertSame('Sales', $role->fresh()->name);
    }

    public function test_users_without_role_permissions_cannot_load_role_details(): void
    {
        Gate::before(fn ($user, $ability) => false);
        $role = $this->makeRole();

        $this->getJson("/api/roles/{$role->id}")->assertForbidden();
    }
}
