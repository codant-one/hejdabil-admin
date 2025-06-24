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

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('reg_num', 'LIKE', '%' . $search . '%')
              ->orWhere('note', 'LIKE', '%' . $search . '%')
              ->orWhere('name', 'LIKE', '%' . $search . '%')
              ->orWhere('phone', 'LIKE', '%' . $search . '%')
              ->orWhere('email', 'LIKE', '%' . $search . '%')
              ->orWhere('comment', 'LIKE', '%' . $search . '%');
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
    public static function createNote($request) {

        $note = self::create([
            'user_id' => Auth::user()->id,
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
}
