<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Plan;
use App\Models\FeaturePlan;
use App\Models\Supplier;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = [ 
            [
                'name' => 'Swish Go',
                'state_id' => 2,
                'description' => 'Ett exklusivt erbjudande som endast gäller för Swish-betalningar',
                'price_month' => 499,
                'price_annual' => 5399,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [

                'name' => 'Pro',
                'state_id' => 2,
                'description' => 'Hela planen för Bilflogg',
                'price_month' => 999,
                'price_annual' => 10799,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        Plan::insert($plans);

        $feature_plans = [
            [
                'plan_id' => 1, 
                'feature_id' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'plan_id' => 2, 
                'feature_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'plan_id' => 2, 
                'feature_id' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'plan_id' => 2, 
                'feature_id' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'plan_id' => 2, 
                'feature_id' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'plan_id' => 2, 
                'feature_id' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'plan_id' => 2, 
                'feature_id' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        FeaturePlan::insert($feature_plans);

        $suppliers = Supplier::whereNull('boss_id')->get();

        foreach ($suppliers as $supplier) {
            $supplier->plan_id = 2;
            $supplier->save();
        }

        
    }
}
