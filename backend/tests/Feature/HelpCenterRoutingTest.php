<?php

namespace Tests\Feature;

use App\Http\Controllers\HelpCenterController;
use App\Models\HelpCategory;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HelpCenterRoutingTest extends TestCase
{
    public function test_categories_endpoint_returns_categories_with_video_counts(): void
    {
        $category = new HelpCategory(['name' => 'Overview', 'label' => 'summary']);
        $category->setAttribute('videos_count', 3);

        Cache::shouldReceive('remember')
            ->once()
            ->with('help-categories.active-with-active-videos', CacheService::CACHE_DURATION, \Mockery::type('Closure'))
            ->andReturn(collect([$category]));

        $this->withoutMiddleware()
            ->getJson('/api/help-center/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.categories.0.name', 'Overview')
            ->assertJsonPath('data.categories.0.videos_count', 3);
    }

    public function test_help_center_only_registers_implemented_controller_actions(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/help-center', 'GET'));

        $this->assertSame(HelpCenterController::class.'@index', $route->getActionName());

        foreach (Route::getRoutes() as $route) {
            if ($route->getControllerClass() === HelpCenterController::class) {
                $this->assertContains($route->getActionMethod(), ['index', 'categories', 'category']);
            }
        }
    }
}
