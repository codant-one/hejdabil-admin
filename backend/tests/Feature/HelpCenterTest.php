<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckUserInactivity;
use App\Http\Middleware\EnforceSubscriptionReadOnly;
use App\Http\Middleware\VerifyJwt;
use App\Models\HelpModule;
use App\Models\HelpVideo;
use App\Models\SupplierActivity;
use App\Models\User;
use Database\Seeders\HelpModuleSeeder;
use Database\Seeders\HelpVideoSeeder;
use Database\Seeders\StateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        $this->withoutMiddleware([
            VerifyJwt::class, CheckUserInactivity::class,
            EnforceSubscriptionReadOnly::class, ThrottleRequests::class,
        ]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('states', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->timestamps();
        });
        (new StateSeeder())->run();

        (require database_path('migrations/2026_11_04_010000_create_help_modules_table.php'))->up();
        (require database_path('migrations/2026_11_04_010001_create_help_videos_table.php'))->up();

        require_once database_path('migrations/2023_06_05_003546_create_permission_tables.php');
        (new \CreatePermissionTables())->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::create('supplier_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('entity_id');
            foreach (['entity_type', 'action_type', 'title', 'description', 'icon', 'route', 'metadata'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            Permission::create(['name' => $action.' help', 'guard_name' => 'api']);
        }
        Permission::create(['name' => 'administrator', 'guard_name' => 'api']);
        Role::create(['name' => 'Admin', 'guard_name' => 'api']);
        Role::create(['name' => 'User', 'guard_name' => 'api']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function login(array $permissions = [], string $role = 'Admin'): User
    {
        $user = User::factory()->create(['password' => 'test-password']);
        $user->assignRole($role);
        $user->givePermissionTo($permissions);
        $this->actingAs($user, 'api');

        return $user;
    }

    private function videoData(): array
    {
        return [
            'help_module_id' => HelpModule::factory()->create()->id,
            'state_id' => 2,
            'title' => 'Så läser du översikten',
            'description' => 'En guide till översikten',
            'video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'provider' => 'youtube',
            'thumbnail_url' => 'https://img.youtube.com/vi/aqz-KE-bpKQ/hqdefault.jpg',
        ];
    }

    public function test_center_lists_only_active_videos_with_relations_for_normal_users(): void
    {
        $this->login([], 'User');
        $active = HelpVideo::factory()->create();
        HelpVideo::factory()->create(['state_id' => 1]);
        HelpVideo::factory()->create(['state_id' => 3]);

        $this->getJson('/api/help-center?state_id=1')
            ->assertOk()->assertJsonPath('data.videos.total', 1)
            ->assertJsonPath('data.videos.data.0.id', $active->id)
            ->assertJsonPath('data.videos.data.0.module.label', $active->module->label)
            ->assertJsonPath('data.videos.data.0.state.name', 'Aktiv')
            ->assertJsonPath('data.videos.data.0.embed_url', 'https://www.youtube.com/embed/aqz-KE-bpKQ');
    }

    public function test_module_and_search_filters_are_grouped_and_optional(): void
    {
        $this->login([], 'User');
        $module = HelpModule::factory()->create();
        $match = HelpVideo::factory()->for($module, 'module')->create([
            'title' => 'Dashboard 0', 'description' => 'needle tutorial',
        ]);
        HelpVideo::factory()->for($module, 'module')->create(['title' => 'Other', 'description' => null]);
        HelpVideo::factory()->create(['title' => 'Another tutorial', 'description' => 'needle tutorial']);
        HelpVideo::factory()->for($module, 'module')->create([
            'title' => 'Inactive tutorial', 'description' => 'needle tutorial', 'state_id' => 1,
        ]);

        $this->getJson('/api/help-center?module='.$module->id.'&search=needle')
            ->assertOk()->assertJsonPath('data.videos.total', 1)
            ->assertJsonPath('data.videos.data.0.id', $match->id);
        $this->getJson('/api/help-center?search=board')->assertJsonPath('data.videos.total', 1);
        $this->getJson('/api/help-center?search=0')->assertJsonPath('data.videos.total', 1);
        $this->getJson('/api/help-center?module=&search=%20')->assertJsonPath('data.videos.total', 3);
        $this->getJson('/api/help-center?module=999999')->assertJsonPath('data.videos.total', 0);
    }

    #[DataProvider('sorts')]
    public function test_sorting(string $sort, array $expected): void
    {
        $this->login([], 'User');
        $module = HelpModule::factory()->create();
        HelpVideo::factory()->for($module, 'module')->create(['title' => 'Alpha', 'created_at' => '2025-02-01 00:00:00']);
        HelpVideo::factory()->for($module, 'module')->create(['title' => 'Zulu', 'created_at' => '2025-01-01 00:00:00']);

        $response = $this->getJson('/api/help-center?sort='.$sort)->assertOk();
        $this->assertSame($expected, array_column($response->json('data.videos.data'), 'title'));
    }

    public static function sorts(): array
    {
        return [
            ['newest', ['Alpha', 'Zulu']],
            ['oldest', ['Zulu', 'Alpha']],
            ['title_asc', ['Alpha', 'Zulu']],
            ['title_desc', ['Zulu', 'Alpha']],
        ];
    }

    public function test_pagination_preserves_filters_and_eager_loading_has_constant_query_count(): void
    {
        $this->login([], 'User');
        $module = HelpModule::factory()->create();
        HelpVideo::factory()->count(12)->for($module, 'module')->create(['title' => 'Tutorial']);
        DB::enableQueryLog();
        $response = $this->getJson('/api/help-center?module='.$module->id.'&search=Tutorial&sort=oldest&limit=6')
            ->assertOk()->assertJsonCount(6, 'data.videos.data')->assertJsonPath('data.videos.total', 12);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $url = $response->json('data.videos.next_page_url');
        $this->assertStringContainsString('module='.$module->id, $url);
        $this->assertStringContainsString('search=Tutorial', $url);
        $this->assertStringContainsString('sort=oldest', $url);
        $this->assertLessThanOrEqual(6, count($queries));
        $this->getJson($url)->assertOk()->assertJsonPath('data.videos.current_page', 2);
    }

    public function test_admin_video_crud_requires_each_permission_and_records_activity(): void
    {
        $user = $this->login(['view help', 'create help', 'edit help', 'delete help']);
        $payload = $this->videoData();
        $id = $this->postJson('/api/help', $payload)->assertOk()->json('data.video.id');
        $this->getJson('/api/help?state_id=2&module='.$payload['help_module_id'])
            ->assertOk()->assertJsonPath('data.videos.total', 1);
        $this->getJson('/api/help/'.$id)->assertOk()->assertJsonPath('data.video.title', $payload['title']);
        $this->putJson('/api/help/'.$id, [...$payload, 'title' => 'Updated', 'state_id' => 1])
            ->assertOk()->assertJsonPath('data.video.state_id', 1);
        $this->getJson('/api/help/updateState/'.$id)->assertOk()->assertJsonPath('data.video.state_id', 2);
        $this->deleteJson('/api/help/'.$id)->assertOk();
        $this->assertDatabaseMissing('help_videos', ['id' => $id]);
        $this->assertSame(4, SupplierActivity::count());
        $this->assertSame($user->id, SupplierActivity::first()->user_id);
        $this->assertSame('Updated', json_decode(SupplierActivity::latest('id')->first()->metadata, true)['old_values']['title']);
    }

    public function test_normal_user_cannot_access_any_admin_action(): void
    {
        $this->login([], 'User');
        $video = HelpVideo::factory()->create();
        $this->getJson('/api/help')->assertForbidden();
        $this->getJson('/api/help/'.$video->id)->assertForbidden();
        $this->postJson('/api/help', [])->assertForbidden();
        $this->putJson('/api/help/'.$video->id, [])->assertForbidden();
        $this->deleteJson('/api/help/'.$video->id)->assertForbidden();
        $this->getJson('/api/help/updateState/'.$video->id)->assertForbidden();
    }

    public function test_read_permission_does_not_grant_write_permissions(): void
    {
        $this->login(['view help']);
        $video = HelpVideo::factory()->create();
        $this->getJson('/api/help')->assertOk();
        $this->postJson('/api/help', [])->assertForbidden();
        $this->putJson('/api/help/'.$video->id, [])->assertForbidden();
        $this->deleteJson('/api/help/'.$video->id)->assertForbidden();
        $this->getJson('/api/help/updateState/'.$video->id)->assertForbidden();
    }

    public function test_administrator_override_can_manage_help(): void
    {
        $this->login(['administrator']);
        $this->postJson('/api/help', $this->videoData())->assertOk();
    }

    public function test_guest_is_rejected_by_existing_jwt_middleware(): void
    {
        $this->withMiddleware(VerifyJwt::class);
        $this->getJson('/api/help-center')->assertUnauthorized();
        $this->getJson('/api/help')->assertUnauthorized();
    }

    public function test_modules_are_available_for_video_selection_without_crud_routes(): void
    {
        $this->login(['view help', 'create help', 'edit help', 'delete help']);
        $this->seed(HelpModuleSeeder::class);
        $this->getJson('/api/help')->assertOk()
            ->assertJsonCount(15, 'data.modules')
            ->assertJsonPath('data.modules.0.name', 'Översikt')
            ->assertJsonPath('data.modules.0.label', 'summary');
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('help-modules.index'));
        $this->getJson('/api/help-modules')->assertNotFound();
        $this->postJson('/api/help-modules', [])->assertNotFound();
        $this->putJson('/api/help-modules/1', [])->assertNotFound();
        $this->deleteJson('/api/help-modules/1')->assertNotFound();
    }

    public function test_requests_validate_fields_and_provider_urls(): void
    {
        $this->login(['create help', 'edit help']);
        $this->postJson('/api/help', [])->assertStatus(400)->assertJsonPath('feedback', 'params_validation_failed');
        $payload = $this->videoData();
        foreach ([
            ['title' => str_repeat('a', 256)],
            ['video_url' => 'not-a-url'],
            ['video_url' => 'https://example.test/not-youtube'],
            ['video_url' => 'javascript:alert(1)'],
            ['video_url' => 'ftp://example.test/video.mp4', 'provider' => 'mp4'],
            ['provider' => 'invalid'],
            ['help_module_id' => 99999],
            ['state_id' => 99999],
            ['thumbnail_url' => 'not-a-url'],
            ['thumbnail_url' => 'https://example.test/'.str_repeat('a', 500)],
        ] as $invalid) {
            $this->postJson('/api/help', [...$payload, ...$invalid])->assertStatus(400);
        }
        $this->postJson('/api/help', [
            ...$payload, 'title' => str_repeat('a', 255),
            'description' => null, 'thumbnail_url' => null, 'provider' => 'mp4',
            'video_url' => 'https://example.test/'.str_repeat('a', 255 - strlen('https://example.test/')),
        ])->assertOk();
    }

    public function test_invalid_filters_are_rejected_and_missing_records_return_404(): void
    {
        $this->login(['view help', 'edit help', 'delete help']);
        foreach (['sort=invalid', 'module=abc', 'orderByField=malicious', 'orderBy=invalid', 'limit=0', 'page=0'] as $query) {
            $this->getJson('/api/help?'.$query)->assertUnprocessable();
        }
        $this->getJson('/api/help/99999')->assertNotFound();
        $this->getJson('/api/help/updateState/99999')->assertNotFound();
        $this->deleteJson('/api/help/99999')->assertNotFound();
        $this->putJson('/api/help/99999', $this->videoData())->assertNotFound();
    }

    public function test_help_only_offers_and_accepts_active_and_inactive_states(): void
    {
        $this->login(['view help', 'create help', 'edit help']);
        $this->seed([HelpModuleSeeder::class, HelpVideoSeeder::class]);
        $response = $this->getJson('/api/help')->assertOk()->assertJsonCount(2, 'data.states');
        $this->assertSame([1, 2], array_column($response->json('data.states'), 'id'));
        $video = HelpVideo::firstOrFail();
        $payload = $video->only($video->getFillable());

        foreach ([1, 2] as $stateId) {
            $this->putJson('/api/help/'.$video->id, [...$payload, 'state_id' => $stateId])
                ->assertOk()->assertJsonPath('data.video.state_id', $stateId);
            $this->getJson('/api/help?state_id='.$stateId)->assertOk();
        }

        $this->postJson('/api/help', [...$payload, 'state_id' => 3])
            ->assertStatus(400)->assertJsonValidationErrors('state_id');
        $this->putJson('/api/help/'.$video->id, [...$payload, 'state_id' => 3])
            ->assertStatus(400)->assertJsonValidationErrors('state_id');
        $this->getJson('/api/help?state_id=3')->assertUnprocessable();
    }

    public function test_seeders_are_repeatable_and_match_existing_schema(): void
    {
        foreach ([1, 2] as $run) {
            $this->seed([HelpModuleSeeder::class, HelpVideoSeeder::class]);
        }
        $this->assertSame(15, HelpModule::count());
        $this->assertSame(18, HelpVideo::active()->count());
        $module = HelpModule::where('label', 'summary')->firstOrFail();
        $this->assertSame('Översikt', $module->name);
        $this->assertSame(3, $module->videos()->count());
        $this->assertSame('Så läser du översikten', $module->videos()->orderBy('id')->first()->title);
    }

    #[DataProvider('embedUrls')]
    public function test_embed_urls(string $provider, string $url, ?string $expected): void
    {
        $this->assertSame($expected, (new HelpVideo([
            'provider' => $provider, 'video_url' => $url,
        ]))->embed_url);
    }

    public static function embedUrls(): array
    {
        $youtube = 'https://www.youtube.com/embed/aqz-KE-bpKQ';
        $vimeo = 'https://player.vimeo.com/video/123456789';

        return [
            ['youtube', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ&t=30', $youtube],
            ['youtube', 'https://youtu.be/aqz-KE-bpKQ', $youtube],
            ['youtube', 'https://youtube.com/shorts/aqz-KE-bpKQ', $youtube],
            ['youtube', 'https://m.youtube.com/live/aqz-KE-bpKQ', $youtube],
            ['youtube', $youtube, $youtube],
            ['youtube', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ', $youtube],
            ['vimeo', 'https://vimeo.com/123456789', $vimeo],
            ['vimeo', $vimeo, $vimeo],
            ['vimeo', 'https://vimeo.com/123456789/abc123', $vimeo.'?h=abc123'],
            ['vimeo', $vimeo.'?h=abc123', $vimeo.'?h=abc123'],
            ['mp4', 'https://example.test/video.mp4?token=example', 'https://example.test/video.mp4?token=example'],
            ['youtube', 'https://youtube.com.evil.test/watch?v=aqz-KE-bpKQ', null],
            ['youtube', 'https://youtube.com/watch?v[]=bad', null],
            ['vimeo', 'https://example.test/123456789', null],
            ['mp4', 'javascript:alert(1)', null],
        ];
    }
}
