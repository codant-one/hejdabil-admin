<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Role extends \Spatie\Permission\Models\Role
{
    use HasFactory, SoftDeletes;

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        foreach (explode(' ', $search) as $term) {
            $query->where('name', 'LIKE', '%' . $term . '%');
        }
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderBy($orderByField, $orderBy);
    }
    
    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'created_at';
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
    public static function createRole($request) {
        $role = self::create([
            'name' => $request->name
        ]);

        $role->syncPermissions($request->permissions);

        return $role;
    }

    public static function updateRole($request, $role) {
        $role->update([
            'name' => $request->name
        ]);

        $role->permissions()->detach();  
        $role->syncPermissions($request->permissions);

        return $role;
    }

    public static function deleteRole($id) {
        $role = self::find($id);
        $role->permissions()->detach();
        $role->delete();
    }

    /**
     * Restore a deleted item and return activity data
     *
     * @param int $id
     * @param object $metadata
     * @return array
     */
    public static function restoreItem($id, $metadata) {
        $permissions = $metadata->old_values->permissions ?? [];
        $role = self::find($id);

        if (!$role)
            return null;

        $role->permissions()->detach();  
        $role->syncPermissions($permissions);
    }

}
