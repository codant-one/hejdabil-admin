<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationships ****/
    public function tokens(){
        return $this->hasMany(Token::class, 'document_id');
    }

    public function token(){
        return $this->hasOne(Token::class, 'document_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', '%' . $search . '%')
              ->orWhere('description', 'LIKE', '%' . $search . '%')
              ->orWhere('file', 'LIKE', '%' . $search . '%')
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

        if ($filters->get('status') !== null) {
            $query->whereHas('tokens', function($q) use ($filters) {
                $q->where('signature_status', $filters->get('status'));
            });
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
    public static function createDocument($request)
    {
        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        $document = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
            'title' => $request->title ?? 'Untitled Document',
            'description' => $request->description ?? null,
        ]);

        return $document;
    }

    public static function deleteDocument($id)
    {
        $document = self::find($id);
        if ($document) {
            if ($document->file) {
                \Storage::disk('public')->delete($document->file);
            }
            $document->delete();
        }
    }

    public static function sendDocument($request)
    {
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
        $documents = self::with('user')->whereIn('id', $ids)->get();

        if ($documents->isEmpty()) {
            return false;
        }

        $titles = $documents->pluck('title')->unique()->implode(', ');
        $data = ['titles' => $titles];

        $subject = 'Dokument: ' . $titles;

        try {
            \Mail::send(
                'emails.documents.signable',
                $data,
                function ($message) use ($request, $documents, $subject) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $message->to($request->email)->subject($subject);

                    foreach ($documents as $document) {
                        $pathToFile = storage_path('app/public/' . $document->file);

                        if (file_exists($pathToFile)) {
                            $mime = mime_content_type($pathToFile);
                            $message->attach($pathToFile, [
                                'as' => \Illuminate\Support\Str::of($document->file)->afterLast('/'),
                                'mime' => $mime
                            ]);
                        }
                    }
                }
            );

            return true;

        } catch (\Exception $e) {
            \Log::error('Error al enviar correo:', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

