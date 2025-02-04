<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

     /**** Relationship ****/
     public function type() {
        return $this->belongsTo(Type::class, 'type_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('name_en', 'LIKE', '%' . $search . '%')
              ->orWhere('name_se', 'LIKE', '%' . $search . '%')
              ->orWhere('description_en', 'LIKE', '%' . $search . '%')
              ->orWhere('description_se', 'LIKE', '%' . $search . '%');
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
    public static function createInvoice($request) {

        $invoice = self::create([
            'type_id' => $request->type_id,
            'name_en' => $request->name_en,
            'name_se' => $request->name_se,
            'description_en' => $request->description_en === 'null' ? null : $request->description_en,
            'description_se' => $request->description_se === 'null' ? null : $request->description_se
        ]);
        
        return $invoice;
    }

    public static function updateInvoice($request, $invoice) {

        $invoice->update([
            'type_id' => $request->type_id,
            'name_en' => $request->name_en,
            'name_se' => $request->name_se,
            'description_en' => $request->description_en === 'null' ? null : $request->description_en,
            'description_se' => $request->description_se === 'null' ? null : $request->description_se
        ]);

        return $invoice;
    }

    public static function deleteInvoice($id) {
        self::deleteInvoices(array($id));
    }

    public static function deleteInvoices($ids) {
        foreach ($ids as $id) {
            $invoice = self::find($id);
            $invoice->delete();
        }
    }
}
