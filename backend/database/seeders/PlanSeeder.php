<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Feature;
use App\Models\Plan;
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
                'features' => ['payouts'],
            ],
            [
                'name' => 'Pro',
                'state_id' => 2,
                'description' => 'Hela planen för Bilflogg',
                'price_month' => 999,
                'price_annual' => 10799,
                'features' => [
                    'clients',
                    'billings',
                    'invoices',
                    'stock',
                    'sold',
                    'agreements',
                    'signed-documents',
                    'payouts',
                    'notes',
                    'my-team',
                    'company',
                    'sms'
                ],
            ],
        ];

        $seededPlans = [];

        foreach ($plans as $planData) {
            $featureNames = $planData['features'] ?? [];

            unset($planData['features']);

            $plan = Plan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );

            $featureIds = Feature::whereIn('name', $featureNames)->pluck('id')->toArray();
            $now = Carbon::now();

            foreach ($featureIds as $featureId) {
                DB::table('feature_plans')->updateOrInsert(
                    [
                        'plan_id' => $plan->id,
                        'feature_id' => $featureId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            DB::table('feature_plans')
                ->where('plan_id', $plan->id)
                ->whereNotIn('feature_id', $featureIds)
                ->delete();

            $seededPlans[$plan->name] = $plan;
        }

        $defaultSupplierPlan = $seededPlans['Pro'] ?? null;

        if (!$defaultSupplierPlan) {
            return;
        }

        $suppliers = Supplier::whereNull('boss_id')->get();

        foreach ($suppliers as $supplier) {
            $supplier->plan_id = $defaultSupplierPlan->id;
            $supplier->save();
        }
    }
}
