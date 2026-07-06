<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

use App\Models\Feature;

class FeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            ['name' => 'clients'],
            ['name' => 'billings'],
            ['name' => 'invoices'],
            ['name' => 'signed-documents'],
            ['name' => 'payouts'],
            ['name' => 'my-team']
        ]; 
        // Crear o actualizar características
        $this->createOrUpdateFeatures($features);

    }

    /**
     * Create or update features in the database
     *
     * @param array $features
     * @return void
     */
    private function createOrUpdateFeatures(array $features)
    {
        foreach ($features as $featureData) {
            Feature::updateOrCreate(
                ['name' => $featureData['name']], // Search by name
                [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
