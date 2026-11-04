<?php

namespace Tests\Feature;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PushTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
            'jwt.secret' => str_repeat('test-secret', 8),
        ]);

        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name')->default('Test');
            $table->string('last_name')->nullable();
            $table->integer('is_2fa')->default(0);
            $table->string('token_2fa')->nullable();
            $table->timestamp('online')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $migration = require database_path('migrations/2026_11_02_000000_create_push_tokens_table.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_upsert_reassigns_token_by_hash_and_reactivates_session(): void
    {
        $firstUser = $this->user('first@example.com');
        $secondUser = $this->user('second@example.com');
        $token = str_repeat('A', 150);

        $firstJwt = Auth::login($firstUser);
        $this->withToken($firstJwt)->postJson('/api/push-tokens', [
            'token' => $token,
            'platform' => 'android',
            'device_id' => 'device-1',
        ])->assertOk()->assertJsonPath('message', 'push_token_saved');

        $stored = PushToken::query()->sole();
        $this->assertSame($firstUser->id, $stored->user_id);
        $this->assertTrue((bool) $stored->session_active);

        $stored->update(['session_active' => false]);

        $secondJwt = Auth::login($secondUser);
        $this->withToken($secondJwt)->postJson('/api/push-tokens', [
            'token' => $token,
            'platform' => 'ios',
            'device_id' => 'device-2',
        ])->assertOk();

        $this->assertSame(1, PushToken::count());
        $reassigned = PushToken::query()->sole();
        $this->assertSame($secondUser->id, $reassigned->user_id);
        $this->assertSame('ios', $reassigned->platform);
        $this->assertSame('device-2', $reassigned->device_id);
        $this->assertTrue((bool) $reassigned->session_active);
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'email' => $email,
            'password' => 'password',
            'name' => 'Test User',
        ]);
    }
}
