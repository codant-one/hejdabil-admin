<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
    
    /**** Relationship ****/
    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }


    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('description', 'LIKE', '%' . $search . '%')
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

        if ($filters->get('supplier_id') !== null) {
            $query->where('supplier_id', $filters->get('supplier_id'));
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
            $query->where('supplier_id', Auth::user()->supplier->id);
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'User') {
            $query->where('supplier_id', Auth::user()->supplier->boss_id);
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('date_from') && $filters->get('date_to')) {
            $filter = [
                [Carbon::parse($filters->get('date_from'))->format('Y-m-d').' 00:00:00'],
                [Carbon::parse($filters->get('date_to'))->format('Y-m-d').' 23:59:59']
            ];
            $query->whereBetween('created_at', $filter);
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
    public static function createReminder($request) {

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        $reminder = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
            'description' => $request->description,
            'date' => $request->date,
            'is_done' => $request->is_done ?? 0
        ]);
        
        return $reminder;
    }

    public static function updateReminder($request, $reminder) {

        $reminder->update([
            'description' => $request->description,
            'date' => $request->date,
            'is_done' => $request->is_done ?? 0
        ]);
        
        return $reminder;
    }

    public static function updateStateReminder($reminder, $isDone) {

        $isDone = (int) $isDone;

        $reminder->update([
            'is_done' => $isDone,
            'completed_at' => $isDone === 1 ? now() : null,
        ]);

        return $reminder;
    }

    public static function deleteReminder($id) {
        self::deleteReminders(array($id));
    }

    public static function deleteReminders($ids) {
        foreach ($ids as $id) {
            $reminder = self::find($id);
            $reminder->delete();
        }
    }
}
