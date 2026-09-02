<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Alert extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**** Relationship ****/
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', '%' . $search . '%')
              ->orWhere('subtitle', 'LIKE', '%' . $search . '%')
              ->orWhereHas('user', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%')
                         ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            });
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

        if ($filters->get('user_id')) {
            $query->where('user_id', $filters->get('user_id'));
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

    public static function deleteAlert($id) {
        self::deleteAlerts(array($id));
    }

    public static function deleteAlerts($ids) {
        foreach ($ids as $id) {
            $alert = self::find($id);
            $alert->delete();
        }
    }
}
