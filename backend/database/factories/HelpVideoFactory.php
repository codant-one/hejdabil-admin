<?php

namespace Database\Factories;

use App\Models\HelpModule;
use App\Models\HelpVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpVideo>
 */
class HelpVideoFactory extends Factory
{
    protected $model = HelpVideo::class;

    public function definition(): array
    {
        return [
            'help_module_id' => HelpModule::factory(),
            'title' => $this->faker->randomElement([
                'Getting started with the dashboard',
                'How to create a customer',
                'How to send an invoice',
                'Managing your vehicle inventory',
                'How to sign an agreement',
            ]),
            'description' => $this->faker->paragraph(),
            'video_url' => 'https://www.youtube.com/embed/aqz-KE-bpKQ',
            'provider' => 'youtube',
            'thumbnail_url' => 'https://img.youtube.com/vi/aqz-KE-bpKQ/hqdefault.jpg',
            'state_id' => 2,
        ];
    }
}
