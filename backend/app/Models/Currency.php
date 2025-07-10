<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    /**** Relationship ****/
    public function agreement(){
        return $this->hasMany(Agreement::class, 'currency_id', 'id');
    }

    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('name', 'LIKE', '%' . $search . '%')
              ->orWhere('code', 'LIKE', '%' . $search . '%')
              ->orWhere('flag', 'LIKE', '%' . $search . '%');
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
    public static function createCurrency($request) {

        $currency = self::create([
            'name' => $request->name,
            'code' => $request->code,
            'flag' => $request->flag
        ]);
        
        return $currency;
    }

    public static function updateCurrency($request, $currency) {

        $currency->update([
            'name' => $request->name,
            'code' => $request->code,
            'flag' => $request->flag
        ]);

        return $currency;
    }

    public static function deleteCurrency($id) {
        self::deleteCurrencies(array($id));
    }

    public static function deleteCurrencies($ids) {
        foreach ($ids as $id) {
            $currency = self::find($id);
            $currency->delete();
        }
    }
}
