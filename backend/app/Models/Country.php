<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Country extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function state() {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('name', 'LIKE', '%' . $search . '%')
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
    public static function createCountry($request) {

        $country = self::create([
            'name' => $request->name,
            'iso' => $request->iso,
            'iso3' => $request->iso3,
            'numcode' => $request->numcode,
            'phonecode' => $request->phonecode,
            'phone_digits' => $request->phone_digits
        ]);
        
        return $country;
    }

    public static function updateCountry($request, $country) {

        $country->update([
            'name' => $request->name,
            'iso' => $request->iso,
            'iso3' => $request->iso3,
            'numcode' => $request->numcode,
            'phonecode' => $request->phonecode,
            'phone_digits' => $request->phone_digits
        ]);

        return $country;
    }

    public static function deleteCountry($id) {
        self::deleteCountries(array($id));
    }

    public static function deleteCountries($ids) {
        foreach ($ids as $id) {
            $country = self::find($id);
            $country->delete();
        }
    }
}