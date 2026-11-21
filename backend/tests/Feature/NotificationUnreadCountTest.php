<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationUnreadCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'jwt.secret' => str_repeat('test-secret', 8),
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('read')->default(false);
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_unread_count_only_counts_the_authenticated_users_unread_notifications(): void
    {
        $user = User::create(['email' => 'user@example.test', 'password' => 'password', 'name' => 'Test']);
        $other = User::create(['email' => 'other@example.test', 'password' => 'password', 'name' => 'Other']);
        DB::table('notifications')->insert([
            ['user_id' => $user->id, 'read' => false],
            ['user_id' => $user->id, 'read' => false],
            ['user_id' => $user->id, 'read' => true],
            ['user_id' => $other->id, 'read' => false],
            ['user_id' => null, 'read' => false],
        ]);

        $this->withToken(Auth::login($user))
            ->getJson('/api/notifications/unread-count?user_id='.$other->id)
            ->assertOk()->assertExactJson(['count' => 2]);

        DB::table('notifications')->where('user_id', $user->id)->update(['read' => true]);
        $this->getJson('/api/notifications/unread-count')
            ->assertOk()->assertExactJson(['count' => 0]);
    }

    public function test_unread_count_requires_jwt(): void
    {
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
    }
}
