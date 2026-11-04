<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\PushToken;
use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Mockery;
use Tests\TestCase;

class FcmPushServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
            'queue.default' => 'array',
        ]);

        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name')->default('Test');
            $table->string('last_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('notification_id')->nullable();
            $table->string('title');
            $table->string('subtitle');
            $table->longText('text');
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->string('route');
            $table->tinyInteger('read')->default(0);
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_11_02_000000_create_push_tokens_table.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_deletes_invalid_token_when_fcm_returns_not_found(): void
    {
        $user = $this->createUser();
        $notification = $this->createNotification($user);
        $pushToken = PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'invalid-token-value',
            'platform' => 'android',
            'device_id' => 'device-a',
            'session_active' => true,
            'notifications_enabled' => true,
        ]);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')
            ->once()
            ->andThrow(NotFound::becauseTokenNotFound($pushToken->token));

        $service = new FcmPushService($messaging);
        $service->send($user, $notification);

        $this->assertDatabaseMissing('push_tokens', ['id' => $pushToken->id]);
    }

    public function test_it_sends_generic_text_for_inactive_sessions(): void
    {
        $user = $this->createUser();
        $notification = $this->createNotification($user);

        PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'valid-token-value',
            'platform' => 'ios',
            'device_id' => 'device-b',
            'session_active' => false,
            'notifications_enabled' => true,
        ]);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')
            ->once()
            ->withArgs(function ($message) use ($notification) {
                $payload = $message->jsonSerialize();

                return ($payload['notification']['title'] ?? null) === 'Bilflogg'
                    && ($payload['notification']['body'] ?? null) === 'Du har en ny notis'
                    && ($payload['data']['notification_id'] ?? null) === (string) $notification->id
                    && ! array_key_exists('route', $payload['data'])
                    && ! array_key_exists('type', $payload['data']);
            });

        $service = new FcmPushService($messaging);
        $service->send($user, $notification);
    }

    private function createUser(): User
    {
        return User::query()->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'name' => 'Test User',
        ]);
    }

    private function createNotification(User $user): Notification
    {
        Queue::fake();

        return Notification::query()->create([
            'user_id' => $user->id,
            'notification_id' => 'ABC',
            'title' => 'Original title',
            'subtitle' => 'Original subtitle',
            'text' => 'Original text',
            'icon' => 'bell',
            'route' => '/dashboard/panel',
            'read' => false,
        ]);
    }
}
