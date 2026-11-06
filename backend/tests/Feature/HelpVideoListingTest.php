<?php

namespace Tests\Feature;

use App\Http\Controllers\HelpVideoController;
use App\Http\Requests\HelpVideoRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                'description' => 'Getting started',
                'provider' => 'youtube',
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ]);
        }
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
}
