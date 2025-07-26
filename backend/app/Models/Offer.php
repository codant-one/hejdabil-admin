<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\CarModel;

class Offer extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function model(){
        return $this->belongsTo(CarModel::class, 'model_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('fullname', 'LIKE', '%' . $search . '%')
            ->orWhere('email', 'LIKE', '%' . $search . '%');
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
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
    public static function createOffer($request) {

        if($request->model_id === '0') {//other model
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);

            $model_id = $model->id;
        } else 
            $model_id = $request->model_id === 'null' ? null : $request->model_id;

        $offer = self::create([
            'user_id' => Auth::user()->id,
            'model_id' => $model_id,
            'offer_id' => $request->offerId === 'null' ? null : $request->offerId,
            'reg_num' => $request->reg_num === 'null' ? null : $request->reg_num,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'comment' => $request->comment === 'null' ? null : $request->comment,
            'price' => $request->price === 'null' ? null : $request->price,
            'terms_other_conditions' => $request->terms_other_conditions === 'null' ? null : $request->terms_other_conditions
        ]);

        return $offer;
    }

    public static function updateOffer($request, $offer) {

        if($request->model_id === '0') {//other model
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);

            $model_id = $model->id;
        } else 
            $model_id = $request->model_id === 'null' ? null : $request->model_id;

        $offer->update([
            'model_id' => $model_id,
            'offer_id' => $request->offerId === 'null' ? null : $request->offerId,
            'reg_num' => $request->reg_num === 'null' ? null : $request->reg_num,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'comment' => $request->comment === 'null' ? null : $request->comment,
            'price' => $request->price === 'null' ? null : $request->price,
            'terms_other_conditions' => $request->terms_other_conditions === 'null' ? null : $request->terms_other_conditions
        ]);

        return $offer;
    }

    public static function deleteOffer($id) {
        self::deleteOffers(array($id));
    }

    public static function deleteOffers($ids) {
        foreach ($ids as $id) {
            $offer = self::find($id);
            $offer->delete();
        }
    }

}