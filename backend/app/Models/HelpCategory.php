<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label', 'icon'];

    /**** Relationship ****/
    public function videos() {
        return $this->hasMany(HelpVideo::class, 'help_category_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($query) use ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('label', 'LIKE', '%' . $search . '%')
                  ->orWhere('icon', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

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
    public static function createHelpCategory($request) {

        $helpCategory = self::create([
            'name' => $request->name,
            'label' => $request->label,
            'icon' => $request->input('icon', '')
        ]);
        
        return $helpCategory;
    }

    public static function updateHelpCategory($request, $helpCategory) {

        $data = [
            'name' => $request->name,
            'label' => $request->label,
        ];

        if ($request->filled('icon')) {
            $data['icon'] = $request->icon;
        }

        $helpCategory->update($data);

        return $helpCategory;
    }

    public static function deleteHelpCategory($id) {
        self::deleteHelpCategories(array($id));
    }

    public static function deleteHelpCategories($ids) {
        foreach ($ids as $id) {
            $helpCategory = self::find($id);
            $helpCategory->delete();

            if($helpCategory->icon)
                deleteFile($helpCategory->icon);
        }
    }
}
