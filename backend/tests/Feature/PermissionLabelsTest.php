<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermissionLabelsTest extends TestCase
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

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('guard_name');
        });

        DB::table('permissions')->insert([
            ['name' => 'create clients', 'label' => 'Registrera en kund', 'guard_name' => 'api'],
            ['name' => 'delete clients', 'label' => 'Radera en kund', 'guard_name' => 'api'],
        ]);
        Cache::forget('permissions.all');
    }

    public function test_permission_labels_are_returned_from_the_database_when_requested(): void
    {
        $this->withoutMiddleware()
            ->getJson('/api/permissions/permission/all?with_labels=true')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'permissions' => [
                        ['name' => 'create clients', 'label' => 'Registrera en kund'],
                        ['name' => 'delete clients', 'label' => 'Radera en kund'],
                    ],
                ],
            ]);

        DB::table('permissions')->where('name', 'create clients')->update(['label' => 'Ny etikett']);

        $this->getJson('/api/permissions/permission/all?with_labels=true')
            ->assertOk()
            ->assertJsonPath('data.permissions.0.label', 'Ny etikett');
    }

    public function test_default_permission_response_preserves_the_existing_name_list(): void
    {
        $this->withoutMiddleware()
            ->getJson('/api/permissions/permission/all')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => ['permissions' => ['create clients', 'delete clients']],
            ]);
    }
}
