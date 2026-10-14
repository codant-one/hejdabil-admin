<?php

namespace Tests\Feature;

use App\Console\Commands\DeleteAccount;
use App\Events\ForceLogoutUserEvent;
use App\Models\DeviceToken;
use App\Models\Supplier;
use App\Models\User;
use App\Services\LoginResponseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    private ?string $originalDebug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDebug = Env::getRepository()->get('APP_DEBUG');
        Env::getRepository()->set('APP_DEBUG', 'false');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
            'auth.device_login_bypasses_2fa' => false,
            'jwt.secret' => str_repeat('test-secret', 8),
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name')->default('Test');
            $table->integer('is_2fa')->default(0);
            $table->string('token_2fa')->nullable();
            $table->timestamp('online')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $migration = require database_path('migrations/2026_10_14_000000_create_device_tokens_table.php');
        $migration->up();

        $service = Mockery::mock(LoginResponseService::class)->makePartial();
        $service->shouldReceive('tokenData')->andReturnUsing(function (User $user, string $token) {
            return [
                'accessToken' => $token,
                'token_type' => 'bearer',
                'user_data' => ['id' => $user->id, 'is_2fa' => $user->is_2fa],
                'userAbilities' => [['action' => 'view', 'subject' => 'Auth']],
            ];
        });
        $this->app->instance(LoginResponseService::class, $service);
    }

    protected function tearDown(): void
    {
        if ($this->originalDebug === null) {
            Env::getRepository()->clear('APP_DEBUG');
        } else {
            Env::getRepository()->set('APP_DEBUG', $this->originalDebug);
        }
        $this->travelBack();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_issuance_stores_only_a_hash_and_expires_in_90_days(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = $this->user();
        $jwt = Auth::login($user);

        $response = $this->withToken($jwt)->postJson('/api/auth/device-token')
            ->assertCreated()->assertJsonPath('message', 'device_token_created');
        $plaintext = $response->json('data.token');
        $stored = DeviceToken::sole();

        $this->assertSame(64, strlen($plaintext));
        $this->assertSame(hash('sha256', $plaintext), $stored->token_hash);
        $this->assertSame($user->id, $stored->user_id);
        $this->assertTrue($stored->expires_at->equalTo(now()->addDays(90)));
        $this->assertNull($stored->last_used_at);
        $this->assertNull($stored->revoked_at);
        $this->assertArrayNotHasKey('token_hash', $stored->toArray());
        $this->assertArrayNotHasKey('token', $stored->toArray());
    }

    public function test_device_login_matches_normal_login_and_updates_last_used(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = $this->user();
        [$plaintext, $stored] = $this->deviceToken($user);

        $normal = $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'original-password',
        ])->assertOk()->json();
        Auth::forgetGuards();
        $device = $this->postJson('/api/auth/device-login', ['token' => $plaintext])
            ->assertOk()->assertJsonPath('message', 'login_success')->json();

        $this->assertSame(array_keys($normal), array_keys($device));
        $this->assertSame(array_keys($normal['data']), array_keys($device['data']));
        unset($normal['data']['accessToken'], $device['data']['accessToken']);
        $this->assertSame($normal, $device);
        $this->assertTrue($stored->fresh()->last_used_at->equalTo(now()));
        $this->assertTrue($user->fresh()->online->equalTo(now()));
    }

    public static function invalidTokens(): array
    {
        return [
            'unknown' => ['unknown'],
            'expired' => ['expired'],
            'expires now' => ['boundary'],
            'revoked' => ['revoked'],
            'missing expiry' => ['no-expiry'],
            'missing user' => ['deleted-user'],
            'malformed' => ['malformed'],
            'not a string' => ['array'],
            'missing token' => ['missing'],
        ];
    }

    #[DataProvider('invalidTokens')]
    public function test_invalid_tokens_share_the_same_error(string $state): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = $this->user();
        [$plaintext, $stored] = $this->deviceToken($user);
        if ($state === 'expired') {
            $stored->update(['expires_at' => now()->subSecond()]);
        } elseif ($state === 'boundary') {
            $stored->update(['expires_at' => now()]);
        } elseif ($state === 'revoked') {
            $stored->update(['revoked_at' => now()]);
        } elseif ($state === 'no-expiry') {
            $stored->update(['expires_at' => null]);
        } elseif ($state === 'deleted-user') {
            DB::table('users')->where('id', $user->id)->delete();
        } elseif ($state === 'unknown') {
            $plaintext = Str::random(64);
        } elseif ($state === 'malformed') {
            $plaintext = 'invalid';
        } elseif ($state === 'array') {
            $plaintext = ['invalid'];
        }
        $payload = $state === 'missing' ? [] : ['token' => $plaintext];

        $this->postJson('/api/auth/device-login', $payload)->assertStatus(401)
            ->assertExactJson(['message' => 'invalid_credentials', 'data' => null]);
        $this->assertNull($stored->fresh()?->last_used_at);
    }

    public function test_inactive_user_reuses_the_normal_login_decision(): void
    {
        $user = $this->user();
        [$plaintext, $stored] = $this->deviceToken($user);
        $user->delete();

        $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'original-password',
        ])->assertStatus(403)->assertJsonPath('message', 'user_inactive');
        $this->postJson('/api/auth/device-login', ['token' => $plaintext])->assertStatus(403)
            ->assertExactJson(['message' => 'user_inactive', 'data' => null]);
        $this->assertNull($stored->fresh()->last_used_at);
    }

    public static function twoFactorCases(): array
    {
        return [
            'disabled' => [0, false, null, 'login_success'],
            'required' => [1, false, 'existing-secret', '2fa'],
            'provisioning' => [1, false, null, '2fa-generate'],
            'bypassed' => [1, true, 'existing-secret', 'login_success'],
            'bypassed without secret' => [1, true, null, 'login_success'],
        ];
    }

    #[DataProvider('twoFactorCases')]
    public function test_device_two_factor_policy(int $enabled, bool $bypass, ?string $secret, string $message): void
    {
        Env::getRepository()->set('APP_DEBUG', 'true');
        config(['auth.device_login_bypasses_2fa' => $bypass]);
        $user = $this->user();
        $user->forceFill(['is_2fa' => $enabled, 'token_2fa' => $secret])->save();
        [$plaintext, $stored] = $this->deviceToken($user);

        if ($message === '2fa-generate') {
            $google2fa = Mockery::mock();
            $google2fa->shouldReceive('generateSecretKey')->once()->andReturn('generated-secret');
            $google2fa->shouldReceive('getQRCodeUrl')->once()
                ->with(config('app.name'), $user->email, 'generated-secret')->andReturn('qr-url');
            $this->app->instance('pragmarx.google2fa', $google2fa);
        }

        $response = $this->postJson('/api/auth/device-login', ['token' => $plaintext])
            ->assertOk()->assertJsonPath('message', $message)->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('data.accessToken'));
        $this->assertNotNull($stored->fresh()->last_used_at);
        if ($message === '2fa-generate') {
            $response->assertJsonPath('data.qr', 'qr-url')->assertJsonPath('data.token', 'generated-secret');
            $this->assertSame('generated-secret', $user->fresh()->token_2fa);
        } elseif ($message === '2fa') {
            $response->assertJsonPath('data.token', $secret);
        } else {
            $this->assertArrayNotHasKey('token', $response->json('data'));
        }
    }

    public function test_normal_login_retains_debug_bypass(): void
    {
        Env::getRepository()->set('APP_DEBUG', 'true');
        $user = $this->user();
        $user->forceFill(['is_2fa' => 1])->save();

        $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'original-password',
        ])->assertOk()->assertJsonPath('message', 'login_success');
        $this->assertNull($user->fresh()->token_2fa);
    }

    public static function normalTwoFactorCases(): array
    {
        return [
            'existing secret' => ['existing-secret', '2fa'],
            'provisioning' => [null, '2fa-generate'],
        ];
    }

    #[DataProvider('normalTwoFactorCases')]
    public function test_normal_and_device_login_share_two_factor_responses(?string $secret, string $message): void
    {
        $user = $this->user();
        $user->forceFill(['is_2fa' => 1, 'token_2fa' => $secret])->save();
        [$plaintext] = $this->deviceToken($user);
        if ($secret === null) {
            $google2fa = Mockery::mock();
            $google2fa->shouldReceive('generateSecretKey')->twice()->andReturn('generated-secret');
            $google2fa->shouldReceive('getQRCodeUrl')->twice()
                ->with(config('app.name'), $user->email, 'generated-secret')->andReturn('qr-url');
            $this->app->instance('pragmarx.google2fa', $google2fa);
        }

        $normal = $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'original-password',
        ])->assertOk()->assertJsonPath('message', $message)->json();
        Auth::forgetGuards();
        if ($secret === null) {
            $user->refresh()->forceFill(['token_2fa' => null])->save();
        }
        $device = $this->postJson('/api/auth/device-login', ['token' => $plaintext])
            ->assertOk()->assertJsonPath('message', $message)->json();

        unset($normal['data']['accessToken'], $device['data']['accessToken']);
        $this->assertSame($normal, $device);
    }

    public function test_revocation_is_user_scoped_and_idempotent(): void
    {
        $user = $this->user();
        [, $first] = $this->deviceToken($user);
        [, $second] = $this->deviceToken($user);
        [, $other] = $this->deviceToken($this->user('other@example.test'));
        $jwt = Auth::login($user);

        $this->withToken($jwt)->deleteJson('/api/auth/device-token')->assertOk()
            ->assertExactJson(['message' => 'device_tokens_revoked', 'data' => null]);
        $revokedAt = $first->fresh()->revoked_at;
        $this->assertNotNull($revokedAt);
        $this->assertNotNull($second->fresh()->revoked_at);
        $this->assertNull($other->fresh()->revoked_at);
        $this->travel(1)->minutes();
        $this->withToken($jwt)->deleteJson('/api/auth/device-token')->assertOk();
        $this->assertTrue($first->fresh()->revoked_at->equalTo($revokedAt));
    }

    public function test_password_changes_revoke_all_tokens_but_other_updates_do_not(): void
    {
        $user = $this->user();
        [, $first] = $this->deviceToken($user);
        [, $second] = $this->deviceToken($user);
        [, $other] = $this->deviceToken($this->user('other@example.test'));

        $user->update(['name' => 'Changed']);
        $this->assertNull($first->fresh()->revoked_at);
        $user->update(['password' => Hash::make('new-password')]);
        $this->assertNotNull($first->fresh()->revoked_at);
        $this->assertNotNull($second->fresh()->revoked_at);
        $this->assertNull($other->fresh()->revoked_at);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_public_login_is_throttled_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/device-login', ['token' => Str::random(64)])->assertStatus(401);
        }
        $this->postJson('/api/auth/device-login', ['token' => Str::random(64)])->assertStatus(429);
    }

    public function test_issuance_and_revocation_require_jwt(): void
    {
        $this->postJson('/api/auth/device-token')->assertStatus(401);
        $this->deleteJson('/api/auth/device-token')->assertStatus(401);
        $this->assertSame(0, DeviceToken::count());
    }

    public function test_deleted_user_jwt_cannot_issue_or_revoke_tokens(): void
    {
        $user = $this->user();
        $jwt = Auth::login($user);
        DB::table('users')->where('id', $user->id)->delete();
        Auth::forgetGuards();

        $this->withToken($jwt)->postJson('/api/auth/device-token')->assertStatus(401);
        $this->withToken($jwt)->deleteJson('/api/auth/device-token')->assertStatus(401);
    }

    public function test_existing_account_purge_deletes_device_tokens_without_relying_on_cascade(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('boss_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['model_has_roles', 'model_has_permissions'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->unsignedBigInteger('model_id');
                $table->string('model_type');
            });
        }
        $user = $this->user();
        [, $stored] = $this->deviceToken($user);
        [, $other] = $this->deviceToken($this->user('other@example.test'));
        $supplier = Supplier::create(['user_id' => $user->id]);
        Schema::disableForeignKeyConstraints();
        Event::fake([ForceLogoutUserEvent::class]);

        $purge = new \ReflectionMethod(DeleteAccount::class, 'purgeSupplierAccountBranch');
        $purge->invoke(new DeleteAccount, $supplier->id, false);

        $this->assertNull($stored->fresh());
        $this->assertNull($user->fresh());
        $this->assertNotNull($other->fresh());
        Event::assertDispatched(ForceLogoutUserEvent::class);
    }

    private function user(string $email = 'user@example.test'): User
    {
        return User::create([
            'email' => $email,
            'password' => Hash::make('original-password'),
            'name' => 'Test',
        ]);
    }

    private function deviceToken(User $user): array
    {
        $token = Str::random(64);
        $stored = $user->deviceTokens()->create([
            'token' => $token,
            'expires_at' => now()->addDays(90),
        ]);

        return [$token, $stored];
    }
}
