<?php

namespace Tests\Feature;

use App\Http\Controllers\HelpVideoController;
use App\Http\Requests\HelpVideoRequest;
use App\Services\CacheService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class HelpVideoListingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('help_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->unsignedBigInteger('state_id')->default(2);
        });
        Schema::create('help_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('help_category_id');
            $table->unsignedBigInteger('state_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->nullable();
            $table->string('video_url');
            $table->string('provider');
            $table->string('thumbnail_url')->nullable();
            $table->timestamps();
        });

        DB::table('states')->insert([
            ['id' => 1, 'name' => 'Inaktiv'],
            ['id' => 2, 'name' => 'Aktiv'],
            ['id' => 3, 'name' => 'Other'],
        ]);
        DB::table('help_categories')->insert([
            'id' => 1, 'name' => 'Overview', 'label' => 'summary',
        ]);
        foreach ([1, 2, 3] as $id) {
            DB::table('help_videos')->insert([
                'id' => $id,
                'help_category_id' => 1,
                'state_id' => $id === 1 ? 1 : 2,
                'title' => 'Tutorial '.$id,
                'slug' => 'tutorial-'.$id,
                'description' => 'Getting started',
                'provider' => 'youtube',
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ]);
        }
    }

    public function test_help_video_seeder_generates_slugs_and_updates_existing_seeded_videos(): void
    {
        foreach (['clients', 'billings', 'vehicles', 'agreements', 'documents'] as $label) {
            DB::table('help_categories')->insert(['name' => $label, 'label' => $label]);
        }

        $this->seed(\Database\Seeders\HelpVideoSeeder::class);

        $seededVideos = \App\Models\HelpVideo::where('video_url', 'https://www.youtube.com/embed/aqz-KE-bpKQ')->get();
        $this->assertCount(18, $seededVideos);

        foreach ($seededVideos as $helpVideo) {
            $this->assertSame(\Illuminate\Support\Str::slug($helpVideo->title), $helpVideo->slug);
        }

        DB::table('help_videos')->whereIn('id', $seededVideos->modelKeys())->update(['slug' => null]);
        $this->seed(\Database\Seeders\HelpVideoSeeder::class);

        $this->assertSame(18, \App\Models\HelpVideo::where('video_url', 'https://www.youtube.com/embed/aqz-KE-bpKQ')->count());
        $this->assertSame(0, \App\Models\HelpVideo::whereIn('id', $seededVideos->modelKeys())->whereNull('slug')->count());
    }

    public function test_help_center_category_returns_only_its_active_videos(): void
    {
        DB::table('help_categories')->insert([
            'id' => 2, 'name' => 'Customers', 'label' => 'clients', 'state_id' => 2,
        ]);
        DB::table('help_videos')->where('id', 3)->update(['help_category_id' => 2]);

        $this->withoutMiddleware()
            ->getJson('/api/help-center/category/summary')
            ->assertOk()
            ->assertJsonPath('data.helpCategory.id', 1)
            ->assertJsonPath('data.helpCategory.label', 'summary')
            ->assertJsonPath('data.helpVideosTotalCount', 1)
            ->assertJsonCount(1, 'data.helpVideos')
            ->assertJsonPath('data.helpVideos.0.id', 2)
            ->assertJsonPath('data.helpVideos.0.state_id', 2);
    }

    public function test_help_center_category_returns_all_results_with_search_and_sort(): void
    {
        $this->withoutMiddleware()
            ->getJson('/api/help-center/category/summary?sort=oldest')
            ->assertOk()
            ->assertJsonPath('data.helpVideosTotalCount', 2)
            ->assertJsonCount(2, 'data.helpVideos')
            ->assertJsonPath('data.helpVideos.0.id', 2)
            ->assertJsonPath('data.helpVideos.1.id', 3)
            ->assertJsonMissingPath('data.helpVideos.last_page');

        $this->getJson('/api/help-center/category/summary?search=Tutorial%202&sort=title')
            ->assertOk()
            ->assertJsonPath('data.helpVideosTotalCount', 1)
            ->assertJsonPath('data.helpVideos.0.id', 2);

        $this->getJson('/api/help-center/category/summary?search=missing')
            ->assertOk()
            ->assertJsonCount(0, 'data.helpVideos');

        $this->getJson('/api/help-center/category/summary?sort=invalid')->assertUnprocessable();
    }

    public function test_help_center_category_rejects_missing_and_inactive_categories(): void
    {
        $this->withoutMiddleware()->getJson('/api/help-center/category/missing')->assertNotFound();

        DB::table('help_categories')->where('id', 1)->update(['state_id' => 1]);

        $this->getJson('/api/help-center/category/summary')->assertNotFound();
    }

    public function test_help_center_video_returns_details_and_only_active_related_videos(): void
    {
        DB::table('help_categories')->insert([
            'id' => 2, 'name' => 'Customers', 'label' => 'clients', 'state_id' => 2,
        ]);
        DB::table('help_videos')->insert([
            'help_category_id' => 2, 'state_id' => 2, 'title' => 'Other tutorial',
            'slug' => 'other-tutorial', 'provider' => 'mp4', 'video_url' => 'https://example.com/video.mp4',
        ]);

        $this->withoutMiddleware()
            ->getJson('/api/help-center/video/tutorial-2')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.helpVideo.id', 2)
            ->assertJsonPath('data.helpVideo.slug', 'tutorial-2')
            ->assertJsonPath('data.helpVideo.description', 'Getting started')
            ->assertJsonPath('data.helpVideo.category.label', 'summary')
            ->assertJsonPath('data.helpVideo.embed_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ')
            ->assertJsonCount(1, 'data.relatedHelpVideos')
            ->assertJsonPath('data.relatedHelpVideos.0.id', 3);
    }

    public function test_help_center_video_rejects_missing_inactive_and_inactive_category_videos(): void
    {
        $this->withoutMiddleware()->getJson('/api/help-center/video/missing')->assertNotFound();
        $this->getJson('/api/help-center/video/tutorial-1')->assertNotFound();

        DB::table('help_categories')->where('id', 1)->update(['state_id' => 1]);

        $this->getJson('/api/help-center/video/tutorial-2')->assertNotFound();
    }

    public function test_help_center_video_can_have_no_related_videos(): void
    {
        DB::table('help_videos')->where('id', 3)->update(['state_id' => 1]);

        $this->withoutMiddleware()->getJson('/api/help-center/video/tutorial-2')
            ->assertOk()
            ->assertJsonCount(0, 'data.relatedHelpVideos');
    }

    public function test_help_center_categories_endpoint_returns_active_video_counts(): void
    {
        $this->withoutMiddleware()->getJson('/api/help-center/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.categories.0.name', 'Overview')
            ->assertJsonPath('data.categories.0.videos_count', 2);
    }

    public function test_listing_returns_video_relations_selectors_and_pagination(): void
    {
        $request = Request::create('/api/help-videos', 'GET', [
            'limit' => 2, 'orderByField' => 'id', 'orderBy' => 'desc',
        ]);

        $response = (new HelpVideoController)->index($request);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->status());
        $this->assertSame(3, $payload['data']['helpVideosTotalCount']);
        $this->assertSame(2, $payload['data']['helpVideos']['last_page']);
        $this->assertSame([3, 2], array_column($payload['data']['helpVideos']['data'], 'id'));
        $video = $payload['data']['helpVideos']['data'][0];
        $this->assertSame('Overview', $video['category']['name']);
        $this->assertSame('Aktiv', $video['state']['name']);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $video['embed_url']);
        $this->assertSame([1, 2], array_column($payload['data']['states'], 'id'));
        $this->assertSame('summary', $payload['data']['categories'][0]['label']);
        $this->assertSame(2, $payload['data']['categories'][0]['state_id']);
    }

    public function test_cached_help_categories_only_include_and_count_active_videos(): void
    {
        DB::table('help_categories')->insert([
            ['id' => 2, 'name' => 'Empty category', 'label' => 'empty', 'state_id' => 2],
            ['id' => 3, 'name' => 'Inactive category', 'label' => 'inactive', 'state_id' => 1],
            ['id' => 4, 'name' => 'Inactive videos only', 'label' => 'inactive-videos', 'state_id' => 2],
        ]);
        DB::table('help_videos')->where('id', 3)->update(['help_category_id' => 3]);
        DB::table('help_videos')->insert([
            'help_category_id' => 4,
            'state_id' => 1,
            'title' => 'Inactive tutorial',
            'provider' => 'youtube',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);
        CacheService::clearCache('help-categories.active-with-active-videos');

        foreach (range(1, 2) as $attempt) {
            $categories = CacheService::getHelpCategories()->keyBy('id');

            $this->assertCount(1, $categories);
            $this->assertFalse($categories->has(2));
            $this->assertFalse($categories->has(3));
            $this->assertFalse($categories->has(4));
            $this->assertSame('Overview', $categories[1]->name);
            $this->assertSame(1, $categories[1]->toArray()['videos_count']);
            $this->assertFalse($categories[1]->relationLoaded('videos'));
        }
    }

    public function test_category_filter_includes_active_and_inactive_videos(): void
    {
        DB::table('help_categories')->insert([
            'id' => 2, 'name' => 'Other category', 'label' => 'other', 'state_id' => 1,
        ]);
        DB::table('help_videos')->where('id', 3)->update(['help_category_id' => 2]);

        $request = Request::create('/api/help-videos', 'GET', [
            'help_category_id' => 1, 'limit' => -1,
            'orderByField' => 'id', 'orderBy' => 'desc',
        ]);
        $payload = (new HelpVideoController)->index($request)->getData(true);

        $this->assertSame([2, 1], array_column($payload['data']['helpVideos']['data'], 'id'));
        $this->assertSame([2, 1], array_column($payload['data']['helpVideos']['data'], 'state_id'));
        $this->assertSame([2], array_column($payload['data']['categories'], 'state_id'));

        $request->query->set('state_id', 1);
        $filtered = (new HelpVideoController)->index($request)->getData(true);
        $this->assertSame([1], array_column($filtered['data']['helpVideos']['data'], 'id'));
    }

    public function test_search_and_state_filters_are_combined(): void
    {
        $request = Request::create('/api/help-videos', 'GET', [
            'search' => 'Getting started', 'state_id' => 1, 'limit' => -1,
            'orderByField' => 'id', 'orderBy' => 'desc',
        ]);

        $payload = (new HelpVideoController)->index($request)->getData(true);

        $this->assertSame(1, $payload['data']['helpVideosTotalCount']);
        $this->assertSame([1], array_column($payload['data']['helpVideos']['data'], 'id'));
    }

    public function test_provider_urls_keep_the_existing_preview_behavior(): void
    {
        foreach ([
            ['youtube', 'https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            ['vimeo', 'https://vimeo.com/123456/abc', 'https://player.vimeo.com/video/123456?h=abc'],
            ['mp4', 'https://example.com/tutorial.mp4', 'https://example.com/tutorial.mp4'],
            ['youtube', 'https://example.com/tutorial.mp4', null],
        ] as [$provider, $url, $expected]) {
            $this->assertSame($expected, \App\Models\HelpVideo::embedUrlFor($provider, $url));
        }
    }

    public function test_created_and_updated_videos_include_table_relations(): void
    {
        $attributes = [
            'help_category_id' => 1,
            'state_id' => 1,
            'title' => 'New tutorial',
            'description' => 'Getting started',
            'provider' => 'youtube',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ];
        $controller = new HelpVideoController;
        $request = HelpVideoRequest::create('/api/help-videos', 'POST', $attributes);
        $created = $controller->store($request)->getData(true)['data']['helpVideo'];

        $this->assertSame('Overview', $created['category']['name']);
        $this->assertSame('new-tutorial', $created['slug']);
        $this->assertDatabaseHas('help_videos', ['id' => $created['id'], 'slug' => 'new-tutorial']);
        $this->assertSame('Inaktiv', $created['state']['name']);

        $attributes['title'] = 'Updated tutorial';
        $attributes['state_id'] = 2;
        $request = HelpVideoRequest::create('/api/help-videos/'.$created['id'], 'PUT', $attributes);
        $updated = $controller->update($request, $created['id'])->getData(true)['data']['helpVideo'];

        $this->assertSame('Updated tutorial', $updated['title']);
        $this->assertSame('Overview', $updated['category']['name']);
        $this->assertSame('Aktiv', $updated['state']['name']);

        $toggled = $controller->updateState($created['id'])->getData(true)['data']['helpVideo'];
        $this->assertSame('Overview', $toggled['category']['name']);
        $this->assertSame('Inaktiv', $toggled['state']['name']);
    }

    public function test_thumbnail_upload_is_saved_preserved_and_replaced(): void
    {
        $disk = 'help-video-test-'.Str::random(20);
        Storage::fake($disk);
        Storage::set('public', Storage::disk($disk));

        try {
            $attributes = [
                'help_category_id' => 1,
                'state_id' => 2,
                'title' => 'New tutorial',
                'provider' => 'youtube',
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ];
            $image = fn () => UploadedFile::fake()->createWithContent(
                'thumbnail.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')
            );
            $this->withoutMiddleware();
            $created = $this->post('/api/help-videos', $attributes + ['thumbnail' => $image()], ['Accept' => 'application/json'])
                ->assertOk()->json('data.helpVideo');
            $original = $created['thumbnail_url'];
            $this->assertStringStartsWith('help-videos/', $original);
            Storage::disk('public')->assertExists($original);

            $this->putJson('/api/help-videos/'.$created['id'], $attributes)
                ->assertOk()->assertJsonPath('data.helpVideo.thumbnail_url', $original);

            $replacement = $this->post('/api/help-videos/'.$created['id'], $attributes + [
                '_method' => 'PUT', 'thumbnail' => $image(),
            ], ['Accept' => 'application/json'])->assertOk()->json('data.helpVideo.thumbnail_url');
            $this->assertNotSame($original, $replacement);
            Storage::disk('public')->assertMissing($original);
            Storage::disk('public')->assertExists($replacement);

            $this->deleteJson('/api/help-videos/'.$created['id'])->assertOk();
            Storage::disk('public')->assertMissing($replacement);
        } finally {
            File::deleteDirectory(storage_path('framework/testing/disks/'.$disk));
        }
    }

    public function test_thumbnail_upload_rejects_non_images(): void
    {
        $this->withoutMiddleware()->post('/api/help-videos', [
            'help_category_id' => 1,
            'state_id' => 2,
            'title' => 'New tutorial',
            'provider' => 'youtube',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'thumbnail' => UploadedFile::fake()->createWithContent('thumbnail.txt', 'not an image'),
        ], ['Accept' => 'application/json'])->assertStatus(400)->assertJsonValidationErrors('thumbnail');
    }

    public function test_existing_external_thumbnail_is_preserved_when_editing(): void
    {
        $url = 'https://example.com/thumbnail.png';
        DB::table('help_videos')->where('id', 2)->update(['thumbnail_url' => $url]);

        $this->withoutMiddleware()->putJson('/api/help-videos/2', [
            'help_category_id' => 1,
            'state_id' => 2,
            'title' => 'Updated tutorial',
            'provider' => 'youtube',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertOk()->assertJsonPath('data.helpVideo.thumbnail_url', $url);
    }
}
