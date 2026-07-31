<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

use App\Models\Feature;
use App\Models\FeaturePlan;

class Plan extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**** Relationship ****/
    public function featurePlans() {
        return $this->hasMany(FeaturePlan::class, 'plan_id', 'id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'feature_plans', 'plan_id', 'feature_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', '%' . $search . '%')
              ->orWhere('description', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('id')) {
            $query->where('id', $filters->get('id'));
        }
        
        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('state_id') !== null) {
            $query->where('state_id', $filters->get('state_id'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'order_id';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit) {
        if ($limit == 'all') {
            return collect(['data' => $query->get()]);
        }

        return $query->paginate($limit);
    }

    /**** Public methods ****/
    public static function createPlan($request) {

        $plan = self::create([
            'name' => $request->name,
            'description' => $request->description,
            'state_id' => 2, //$request->state_id,
            'price_month' => $request->price_month,
            'price_annual' => $request->price_annual
        ]);

        $plan->features()->sync(self::extractFeatureIds($request));

        return $plan;
    }

    public static function updatePlan($request, $plan) {

        $plan->update([
            'name' => $request->name,
            'description' => $request->description,
            // 'state_id' => $request->state_id,
            'price_month' => $request->price_month,
            'price_annual' => $request->price_annual
        ]);

        $plan->features()->sync(self::extractFeatureIds($request));
        

        return $plan;
    }

    private static function extractFeatureIds($request): array
    {
        $features = $request->input('features', []);

        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : [];
        }

        return collect($features)
            ->filter(fn ($featureId) => is_numeric($featureId))
            ->map(fn ($featureId) => (int) $featureId)
            ->unique()
            ->values()
            ->all();
    }

    public static function deletePlan($id) {
        self::deletePlans(array($id));
    }

    public static function deletePlans($ids) {
        //Limpia array
        $ids = array_values(array_unique(array_filter($ids)));
        DB::transaction(function () use ($ids) {
            $plans = self::withTrashed()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            foreach ($ids as $id) {
                $plan = $plans->get($id);

                if (!$plan) {
                    continue;
                }

                $plan->state_id = 1;
                $plan->save();

                $plan->delete();
            }
        });
    }
}
