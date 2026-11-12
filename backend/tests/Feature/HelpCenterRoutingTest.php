<?php

namespace Tests\Feature;

use App\Http\Controllers\HelpCenterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HelpCenterRoutingTest extends TestCase
{
    public function test_video_endpoint_routes_to_the_video_action(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/help-center/video/tutorial-2', 'GET'));

        $this->assertSame(HelpCenterController::class.'@video', $route->getActionName());
        $this->assertSame('tutorial-2', $route->parameter('slug'));
    }

    public function test_help_center_only_registers_implemented_controller_actions(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/help-center', 'GET'));

        $this->assertSame(HelpCenterController::class.'@index', $route->getActionName());

        foreach (Route::getRoutes() as $route) {
            if ($route->getControllerClass() === HelpCenterController::class) {
                $this->assertContains($route->getActionMethod(), ['index', 'categories', 'category', 'video']);
            }
        }
    }
}
