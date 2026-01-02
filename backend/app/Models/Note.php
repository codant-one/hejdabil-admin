<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Note extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function comments(){
        return $this->hasMany(NoteComment::class, 'note_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('reg_num', 'LIKE', '%' . $search . '%')
              ->orWhere('note', 'LIKE', '%' . $search . '%')
              ->orWhere('name', 'LIKE', '%' . $search . '%')
              ->orWhere('phone', 'LIKE', '%' . $search . '%')
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
    public static function createNote($request) {

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        $note = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
            'reg_num' => $request->reg_num,
            'note' => $request->note === 'null' ? null : $request->note,
            'name' => $request->name === 'null' ? null : $request->name,
            'phone' => $request->phone === 'null' ? null : $request->phone,
            'email' => $request->email === 'null' ? null : $request->email,
            'comment' => $request->comment === 'null' ? null : $request->comment
        ]);
        
        return $note;
    }

    public static function updateNote($request, $note) {

        $note->update([
            'note' => $request->note === 'null' ? null : $request->note,
            'name' => $request->name === 'null' ? null : $request->name,
            'phone' => $request->phone === 'null' ? null : $request->phone,
            'email' => $request->email === 'null' ? null : $request->email,
            'comment' => $request->comment === 'null' ? null : $request->comment
        ]);

        return $note;
    }

    public static function deleteNote($id) {
        self::deleteNotes(array($id));
    }

    public static function deleteNotes($ids) {
        foreach ($ids as $id) {
            $note = self::find($id);
            $note->delete();
        }
    }

    public static function sendComment($request) {
        NoteComment::create([
            'user_id' => Auth::user()->id,
            'note_id' => $request->id,
            'comment' => $request->comment
        ]);
    }
}
