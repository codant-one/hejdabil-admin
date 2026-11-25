<?php

namespace Tests\Feature;

use App\Events\UserNotificationEvent;
use App\Http\Middleware\CheckUserInactivity;
use App\Http\Middleware\EnforceSubscriptionReadOnly;
use App\Http\Middleware\VerifyJwt;
use App\Jobs\SendBroadcastNotificationChunkJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\AdminNotification;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\StateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BroadcastNotificationTest extends TestCase
{
    private const NOTIFICATION_PERMISSIONS = ['view notifications', 'create notifications', 'delete notifications'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'permission.testing' => true,
            'queue.push_connection' => 'database',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });

        require_once database_path('migrations/2023_06_05_003546_create_permission_tables.php');
        (new \CreatePermissionTables())->up();

        // Real `notifications` schema (create + alter supplier_id).
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
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

        (require database_path('migrations/2023_06_13_131134_create_states_table.php'))->up();
        (new StateSeeder())->run();

        (require database_path('migrations/2026_11_22_000000_create_admin_notifications_table.php'))->up();
        (require database_path('migrations/2026_11_22_000100_add_broadcast_id_to_notifications_table.php'))->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->withoutMiddleware([
            VerifyJwt::class,
            CheckUserInactivity::class,
            EnforceSubscriptionReadOnly::class,
            ThrottleRequests::class,
        ]);

        Queue::fake();
        Event::fake([UserNotificationEvent::class]);

        // As in PermissionSeeder: SuperAdmin and Administrator get the notifications permissions.
        foreach (self::NOTIFICATION_PERMISSIONS as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }
        foreach (['SuperAdmin', 'Administrator', 'Supplier', 'User'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'api']);
        }
        Role::findByName('SuperAdmin', 'api')->givePermissionTo(self::NOTIFICATION_PERMISSIONS);
        Role::findByName('Administrator', 'api')->givePermissionTo(self::NOTIFICATION_PERMISSIONS);

        $this->admin = $this->makeUser('Admin', 'Administrator', 'Adminsson');
        $this->actingAs($this->admin, 'api');
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    private function makeUser(?string $name, ?string $role = null, ?string $lastName = null): User
    {
        static $sequence = 0;
        $sequence++;

        $user = User::create([
            'name' => $name ?? '',
            'last_name' => $lastName,
            'email' => "user{$sequence}@example.test",
            'password' => 'password',
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Hej {{user}}',
            'subtitle' => 'Ny funktion finns nu, {{ USER }}!',
            'route' => '/dashboard/panel',
            'audience' => 'all',
        ], $overrides);
    }

    /** Run the queued chunk jobs, as the cron worker would. */
    private function runChunkJobs(): void
    {
        Queue::pushed(SendBroadcastNotificationChunkJob::class)->each(fn ($job) => app()->call([$job, 'handle']));
    }

    private function pushedNotificationIds(): array
    {
        return Queue::pushed(SendPushNotificationJob::class)
            ->map(fn ($job) => (fn () => $this->notificationId)->call($job))
            ->sort()->values()->all();
    }

    public function test_non_admin_cannot_access_any_endpoint(): void
    {
        $this->actingAs($this->makeUser('Sara', 'Supplier'), 'api');

        $this->getJson('/api/admin/notifications-broadcast')->assertForbidden();
        $this->getJson('/api/admin/notifications-broadcast/roles')->assertForbidden();
        $this->postJson('/api/admin/notifications-broadcast/preview-count', ['audience' => 'all'])->assertForbidden();
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertForbidden();
        $this->deleteJson('/api/admin/notifications-broadcast/1')->assertForbidden();

        $this->assertSame(0, AdminNotification::count());
        Queue::assertNothingPushed();
    }

    public function test_superadmin_role_and_administrator_permission_grant_access(): void
    {
        $this->actingAs($this->makeUser('Super', 'SuperAdmin'), 'api');
        $this->getJson('/api/admin/notifications-broadcast/roles')->assertOk();

        Permission::create(['name' => 'administrator', 'guard_name' => 'api']);
        // User without an administrator role, only with the direct "administrator" permission.
        $userWithPermission = $this->makeUser('Staff');
        $userWithPermission->givePermissionTo('administrator');
        $this->actingAs($userWithPermission, 'api');
        $this->getJson('/api/admin/notifications-broadcast/roles')->assertOk();
    }

    public function test_each_action_requires_its_own_permission(): void
    {
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertCreated();
        $broadcastId = AdminNotification::sole()->id;
        $this->runChunkJobs();

        // view only: history yes, create/delete no
        $viewer = $this->makeUser('Viewer');
        $viewer->givePermissionTo('view notifications');
        $this->actingAs($viewer, 'api');
        $this->getJson('/api/admin/notifications-broadcast')->assertOk();
        $this->getJson('/api/admin/notifications-broadcast/roles')->assertForbidden();
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertForbidden();
        $this->deleteJson("/api/admin/notifications-broadcast/{$broadcastId}")->assertForbidden();

        // create only: can send (and see the history), cannot delete
        $creator = $this->makeUser('Creator');
        $creator->givePermissionTo('create notifications');
        $this->actingAs($creator, 'api');
        $this->getJson('/api/admin/notifications-broadcast')->assertOk();
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertCreated();
        $this->deleteJson("/api/admin/notifications-broadcast/{$broadcastId}")->assertForbidden();

        // delete only: can delete, cannot send
        $deleter = $this->makeUser('Deleter');
        $deleter->givePermissionTo('delete notifications');
        $this->actingAs($deleter, 'api');
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertForbidden();
        $this->deleteJson("/api/admin/notifications-broadcast/{$broadcastId}")->assertOk();
    }

    public function test_deleting_a_sent_broadcast_removes_it_and_the_users_notifications(): void
    {
        $sara = $this->makeUser('Sara', 'Supplier');
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertCreated();
        $broadcast = AdminNotification::sole();
        $this->runChunkJobs();

        // An unrelated notification of the same user must survive.
        $other = Notification::create([
            'user_id' => $sara->id, 'title' => 'Annan', 'subtitle' => 'Annan', 'text' => 'Annan', 'route' => '/dashboard/panel',
        ]);
        $this->assertSame(3, Notification::count());

        $this->deleteJson("/api/admin/notifications-broadcast/{$broadcast->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted_notifications', 2);

        $this->assertSame(0, AdminNotification::count());
        $this->assertSame([$other->id], Notification::pluck('id')->all());

        $this->deleteJson("/api/admin/notifications-broadcast/{$broadcast->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Notisen hittades inte');
    }

    public function test_a_broadcast_in_progress_cannot_be_deleted(): void
    {
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertCreated(); // queued, jobs not run
        $broadcast = AdminNotification::sole();

        $this->deleteJson("/api/admin/notifications-broadcast/{$broadcast->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Notisen skickas fortfarande och kan inte tas bort ännu.');

        $this->assertSame(1, AdminNotification::count());
    }

    public function test_validation_rules_and_swedish_messages(): void
    {
        $this->postJson('/api/admin/notifications-broadcast', [])
            ->assertStatus(400)
            ->assertJsonPath('message.title.0', 'Titeln är obligatorisk.')
            ->assertJsonPath('message.subtitle.0', 'Underrubriken är obligatorisk.')
            ->assertJsonPath('message.audience.0', 'Mottagare är obligatoriskt.');

        $this->postJson('/api/admin/notifications-broadcast', $this->payload([
            'title' => str_repeat('a', 66),
            'subtitle' => str_repeat('b', 179),
        ]))
            ->assertStatus(400)
            ->assertJsonPath('message.title.0', 'Titeln får inte vara längre än 65 tecken.')
            ->assertJsonPath('message.subtitle.0', 'Underrubriken får inte vara längre än 178 tecken.');

        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'roles']))
            ->assertStatus(400)
            ->assertJsonPath('message.roles.0', 'Välj minst en roll.');

        $errors = $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'roles', 'roles' => ['Ghost']]))
            ->assertStatus(400)
            ->json('message');
        $this->assertSame(['Den angivna rollen finns inte.'], $errors['roles.0']);

        // SuperAdmin exists but cannot be targeted.
        $errors = $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'roles', 'roles' => ['SuperAdmin']]))
            ->assertStatus(400)
            ->json('message');
        $this->assertSame(['Den angivna rollen finns inte.'], $errors['roles.0']);

        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'everyone']))
            ->assertStatus(400)
            ->assertJsonPath('message.audience.0', 'Ogiltigt val av mottagare.');

        $this->assertSame(0, AdminNotification::count());
    }

    public function test_external_routes_are_rejected(): void
    {
        foreach (['https://evil.example', '//evil.example', '/\\evil.example', 'dashboard', 'javascript:alert(1)', '/ space'] as $route) {
            $this->postJson('/api/admin/notifications-broadcast', $this->payload(['route' => $route]))
                ->assertStatus(400)
                ->assertJsonPath('message.route.0', 'Länken måste vara en intern sökväg som börjar med "/" (externa adresser är inte tillåtna).');
        }

        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['route' => null]))->assertCreated();
        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['route' => '/apps/vehicles?tab=2']))->assertCreated();
    }

    public function test_roles_endpoint_and_preview_count(): void
    {
        $this->makeUser('Sara', 'Supplier');
        $this->makeUser('Olle', 'Supplier');
        $this->makeUser('Ulla', 'User');
        $this->makeUser('Borta', 'Supplier')->delete(); // inactive

        $roles = collect($this->getJson('/api/admin/notifications-broadcast/roles')
            ->assertOk()
            ->assertJsonPath('data.all_count', 4)
            ->json('data.roles'))->pluck('users_count', 'name');

        // SuperAdmin is never offered as an audience role.
        $this->assertSame(['Administrator' => 1, 'Supplier' => 2, 'User' => 1], $roles->all());

        $this->postJson('/api/admin/notifications-broadcast/preview-count', ['audience' => 'roles', 'roles' => ['Supplier', 'User']])
            ->assertOk()
            ->assertJsonPath('data.recipients_count', 3);

        $this->postJson('/api/admin/notifications-broadcast/preview-count', ['audience' => 'all'])
            ->assertOk()
            ->assertJsonPath('data.recipients_count', 4);
    }

    public function test_send_to_all_active_users_creates_one_notification_per_user_via_eloquent(): void
    {
        $sara = $this->makeUser('Sara', 'Supplier');
        $ulla = $this->makeUser('Ulla', 'User');
        $this->makeUser('Borta', 'User')->delete();

        $response = $this->postJson('/api/admin/notifications-broadcast', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.broadcast.state.label', 'queued')
            ->assertJsonPath('data.broadcast.state.name', 'I kö')
            ->assertJsonPath('data.broadcast.recipients_count', 3);

        Queue::assertPushedOn('push-notifications', SendBroadcastNotificationChunkJob::class);
        Queue::assertPushed(SendBroadcastNotificationChunkJob::class, fn ($job) => $job->connection === 'database');

        $this->runChunkJobs();

        $broadcast = AdminNotification::findOrFail($response->json('data.broadcast.id'));
        $this->assertSame('sent', $broadcast->state->label);
        $this->assertSame(3, $broadcast->recipients_count);
        $this->assertNotNull($broadcast->sent_at);

        $notifications = Notification::where('broadcast_id', $broadcast->id)->orderBy('user_id')->get();
        $this->assertSame([$this->admin->id, $sara->id, $ulla->id], $notifications->pluck('user_id')->all());

        // Observer fired: one SendPushNotificationJob per notification, on the push-notifications queue (not afterResponse).
        $this->assertSame($notifications->pluck('id')->sort()->values()->all(), $this->pushedNotificationIds());
        Queue::assertPushedOn('push-notifications', SendPushNotificationJob::class);

        // Web bell (websocket) per user.
        Event::assertDispatchedTimes(UserNotificationEvent::class, 3);
        Event::assertDispatched(UserNotificationEvent::class, fn ($event) => $event->userId === $sara->id);
    }

    public function test_send_by_role_only_reaches_users_with_that_role(): void
    {
        $sara = $this->makeUser('Sara', 'Supplier');
        $olle = $this->makeUser('Olle', 'Supplier');
        $this->makeUser('Ulla', 'User');

        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'roles', 'roles' => ['Supplier']]))
            ->assertCreated()
            ->assertJsonPath('data.broadcast.roles', ['Supplier'])
            ->assertJsonPath('data.broadcast.recipients_count', 2);

        $this->runChunkJobs();

        $this->assertEqualsCanonicalizing([$sara->id, $olle->id], Notification::pluck('user_id')->all());
        $this->assertCount(2, $this->pushedNotificationIds());
    }

    public function test_send_to_specific_users_only_reaches_those_users(): void
    {
        $sara = $this->makeUser('Sara', 'Supplier');
        $olle = $this->makeUser('Olle', 'Supplier');
        $this->makeUser('Ulla', 'User');

        $this->postJson('/api/admin/notifications-broadcast/preview-count', ['audience' => 'users', 'user_ids' => [$sara->id, $olle->id]])
            ->assertOk()
            ->assertJsonPath('data.recipients_count', 2);

        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'users', 'user_ids' => [$sara->id, $olle->id]]))
            ->assertCreated()
            ->assertJsonPath('data.broadcast.user_ids', [$sara->id, $olle->id])
            ->assertJsonPath('data.broadcast.recipients_count', 2);

        $this->runChunkJobs();

        $this->assertEqualsCanonicalizing([$sara->id, $olle->id], Notification::pluck('user_id')->all());
        $this->assertCount(2, $this->pushedNotificationIds());
    }

    public function test_specific_users_validation(): void
    {
        $deleted = $this->makeUser('Borta', 'User');
        $deleted->delete();

        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'users']))
            ->assertStatus(400)
            ->assertJsonPath('message.user_ids.0', 'Välj minst en användare.');

        $errors = $this->postJson('/api/admin/notifications-broadcast', $this->payload(['audience' => 'users', 'user_ids' => [$deleted->id]]))
            ->assertStatus(400)
            ->json('message');
        $this->assertSame(['Den valda användaren finns inte eller är inaktiv.'], $errors['user_ids.0']);
    }

    public function test_users_search_matches_name_last_name_and_email_and_keeps_selected(): void
    {
        $anna = $this->makeUser('Anna', 'Supplier', 'Svensson');
        $olle = $this->makeUser('Olle', 'User', 'Berg');
        $this->makeUser('Borta', 'User', 'Svensson')->delete();

        $this->getJson('/api/admin/notifications-broadcast/users?search=anna%20svens')
            ->assertOk()
            ->assertJsonCount(1, 'data.users')
            ->assertJsonPath('data.users.0.id', $anna->id)
            ->assertJsonPath('data.users.0.full_name', 'Anna Svensson')
            ->assertJsonPath('data.users.0.roles', ['Supplier']);

        $ids = collect($this->getJson('/api/admin/notifications-broadcast/users?search=svensson&include[]=' . $olle->id)
            ->assertOk()
            ->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$anna->id, $olle->id], $ids);
    }

    public function test_user_placeholder_is_rendered_in_title_and_subtitle(): void
    {
        $sara = $this->makeUser('Sara', 'Supplier', 'Berg');
        $noFirstName = $this->makeUser(null, 'Supplier', 'Lund');
        $noName = $this->makeUser(null, 'Supplier');

        $this->postJson('/api/admin/notifications-broadcast', $this->payload([
            'audience' => 'roles',
            'roles' => ['Supplier'],
            'title' => 'Hej {{user}}',
            'subtitle' => '{{ USER }}, ny funktion för dig {{user}}!',
            'route' => null,
        ]))->assertCreated();

        $this->runChunkJobs();

        $bySara = Notification::where('user_id', $sara->id)->sole();
        $this->assertSame('Hej Sara Berg', $bySara->title);
        $this->assertSame('Sara Berg, ny funktion för dig Sara Berg!', $bySara->subtitle);
        $this->assertSame('Sara Berg, ny funktion för dig Sara Berg!', $bySara->text);
        $this->assertSame('/dashboard/panel', $bySara->route);
        $this->assertSame('custom-alarm', $bySara->icon);

        $this->assertSame('Hej Lund', Notification::where('user_id', $noFirstName->id)->value('title'));

        $empty = Notification::where('user_id', $noName->id)->sole();
        $this->assertSame('Hej', $empty->title);
        $this->assertSame(', ny funktion för dig!', $empty->subtitle);

        // The history stores the unrendered template.
        $this->assertSame('Hej {{user}}', AdminNotification::sole()->title);
    }

    public function test_retrying_a_chunk_job_does_not_duplicate_notifications_or_pushes(): void
    {
        $this->makeUser('Sara', 'Supplier');
        $this->makeUser('Ulla', 'User');

        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertCreated();

        $this->runChunkJobs();
        $this->runChunkJobs(); // retry

        $this->assertSame(3, Notification::count());
        $this->assertCount(3, $this->pushedNotificationIds());
        Event::assertDispatchedTimes(UserNotificationEvent::class, 3);
        $this->assertSame('sent', AdminNotification::sole()->state->label);
        $this->assertSame(3, AdminNotification::sole()->recipients_count);
    }

    public function test_one_failing_user_does_not_stop_the_chunk_and_marks_broadcast_failed(): void
    {
        $sara = $this->makeUser('Sara', 'Supplier');
        $ulla = $this->makeUser('Ulla', 'User');

        Notification::creating(function (Notification $notification) use ($sara) {
            if ((int) $notification->user_id === $sara->id) {
                throw new \RuntimeException('boom');
            }
        });

        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertCreated();
        $this->runChunkJobs();

        $this->assertEqualsCanonicalizing([$this->admin->id, $ulla->id], Notification::pluck('user_id')->all());
        $this->assertSame('failed', AdminNotification::sole()->state->label);
        $this->assertSame(2, AdminNotification::sole()->recipients_count);
    }

    public function test_history_is_paginated(): void
    {
        $this->postJson('/api/admin/notifications-broadcast', $this->payload())->assertCreated();
        $this->postJson('/api/admin/notifications-broadcast', $this->payload(['title' => 'Andra']))->assertCreated();

        $this->getJson('/api/admin/notifications-broadcast?limit=1')
            ->assertOk()
            ->assertJsonPath('data.broadcastsTotalCount', 2)
            ->assertJsonPath('data.broadcasts.data.0.title', 'Andra')
            ->assertJsonPath('data.broadcasts.data.0.user_id', $this->admin->id)
            ->assertJsonPath('data.broadcasts.data.0.user.id', $this->admin->id)
            ->assertJsonPath('data.broadcasts.data.0.state.label', 'queued')
            ->assertJsonPath('data.broadcasts.data.0.state.name', 'I kö');
    }
}
