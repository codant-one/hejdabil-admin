<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Note;
use App\Models\Supplier;
use App\Models\SupplierActivity;
use App\Services\CacheService;

class NoteController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view notes|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create notes|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit notes|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete notes|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = (int) $request->input('limit', 10);

            // Avoid invalid per-page values (0/negative) in paginator calculations.
            if ($limit !== -1)
                $limit = max(1, $limit);
        
            $query = Note::with([
                           'supplier' => function ($q) {
                                $q->select('id', 'user_id', 'deleted_at')
                                  ->withTrashed()
                                  ->with(['user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'deleted_at')->withTrashed()]);
                            },
                            'user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'avatar', 'deleted_at')->withTrashed(),
                            'user.userDetail:user_id,avatar_id,logo',
                            'comments:id,note_id,user_id,comment,created_at',
                            'comments.user:id,name,last_name'
                        ])
                         ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'supplier_id',
                                    'date_from',
                                    'date_to'
                                ])
                            );

            if ($limit == -1) {
                $allNotes = $query->get();
                $perPage = max(1, $allNotes->count());
                $notes = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allNotes,
                    $allNotes->count(),
                    $perPage,
                    1
                );
            } else {
                $notes = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'notes' => $notes,
                    'notesTotalCount' => $notes->total(),
                    'suppliers' => CacheService::getActiveSuppliers()
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
              'success' => false,
              'message' => 'database_error',
              'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NoteRequest $request)
    {
        try {

            $noteFields = [
                'supplier_id', 'reg_num', 'note', 'name', 'phone', 'email', 'comment'
            ];

            $note = Note::createNote($request);

            SupplierActivity::createActivity([
                'entity_id' => $note->id,
                'entity_type' => 'notes',
                'action_type' => 'create_note',
                'title' => $this->noteActivityTitle($note, ' - skapad'),
                'description' => 'Skapades.',
                'icon' => 'custom-cash',
                'route' => '/dashboard/admin/notes',
                'metadata' => json_encode([
                    'note_id' => $note->id,
                    'new_values' => $note->only($noteFields),
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'note' => Note::with(['user'])->find($note->id)
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $note = Note::with(['user'])->find($id);

            if (!$note)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Egen värdering hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'note' => $note
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NoteRequest $request, $id): JsonResponse
    {
        try {
            $note = Note::with(['user'])->find($id);

            $noteFields = [
                'supplier_id', 'reg_num', 'note', 'name', 'phone', 'email', 'comment'
            ];
        
            if (!$note)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Egen värdering hittades inte'
                ], 404);

            $oldValues = $note->only($noteFields);

            $note->updateNote($request, $note); 

            SupplierActivity::createActivity([
                'entity_id' => $note->id,
                'entity_type' => 'notes',
                'action_type' => 'update_note',
                'title' => $this->noteActivityTitle($note, ' - uppdaterad'),
                'description' => 'Uppdaterades.',
                'icon' => 'custom-cash',
                'route' => '/dashboard/admin/notes',
                'metadata' => json_encode([
                    'note_id' => $note->id,
                    'old_values' => $oldValues,
                    'new_values' => $note->only($noteFields),
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'note' => $note
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $note = Note::with(['user'])->find($id);

            $noteFields = [
                'supplier_id', 'reg_num', 'note', 'name', 'phone', 'email', 'comment'
            ];
        
            if (!$note)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Egen värdering hittades inte'
                ], 404);

            $oldValues = $note->only($noteFields);

            SupplierActivity::createActivity([
                'entity_id' => $note->id,
                'entity_type' => 'notes',
                'action_type' => 'delete_note',
                'title' => $this->noteActivityTitle($note, ' - borttagen'),
                'description' => 'Togs bort.',
                'icon' => 'custom-cash',
                'metadata' => json_encode([
                    'note_id' => $note->id,
                    'old_values' => $oldValues,
                ])
            ]);

            SupplierActivity::where('entity_id', $note->id)
                ->where('entity_type', 'notes')
                ->update(['route' => null]);
            
            $note->deleteNote($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'note' => $note
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }


    public function comment(Request $request)
    {
        try {

            $note = Note::find($request->id);

            if (!$note)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Egen värdering hittades inte'
                ], 404);

            Note::sendComment($request);

            SupplierActivity::createActivity([
                'entity_id' => $note->id,
                'entity_type' => 'notes',
                'action_type' => 'send_comment_note',
                'title' => $this->noteActivityTitle($note, ' - kommentar tillagd'),
                'description' => 'Lades till.',
                'icon' => 'custom-cash',
                'route' => '/dashboard/admin/notes',
                'metadata' => json_encode([
                    'note_id' => $note->id,
                    'comment' => $request->comment,
                ])
            ]);

            return response()->json([
                'success' => true
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function updateComment(Request $request, $id)
    {
        try {

            $note = Note::find($request->note_id);

            if (!$note)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Egen värdering hittades inte'
                ], 404);

            $existingComment = $note->comments()->find($id);

            if (!$existingComment)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kommentar hittades inte'
                ], 404);

            $oldValues = [
                'comment_id' => $existingComment->id,
                'comment' => $existingComment->comment,
            ];

            $updatedComment = Note::updateComment($request, $id);

            SupplierActivity::createActivity([
                'entity_id' => $note->id,
                'entity_type' => 'notes',
                'action_type' => 'update_comment_note',
                'title' => $this->noteActivityTitle($note, ' - kommentar uppdaterad'),
                'description' => 'Uppdaterades.',
                'icon' => 'custom-cash',
                'route' => '/dashboard/admin/notes',
                'metadata' => json_encode([
                    'note_id' => $note->id,
                    'old_values' => $oldValues,
                    'new_values' => [
                        'comment_id' => $updatedComment?->id ?? $id,
                        'comment' => $updatedComment?->comment ?? $request->comment,
                    ],
                ])
            ]);

            return response()->json([
                'success' => true
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function deleteComment(Request $request, $id)
    {
        try {

            $note = Note::find($request->note_id);

            if (!$note)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Egen värdering hittades inte'
                ], 404);

            $existingComment = $note->comments()->find($id);

            if (!$existingComment)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kommentar hittades inte'
                ], 404);

            $oldValues = [
                'comment_id' => $existingComment->id,
                'comment' => $existingComment->comment,
            ];

            Note::deleteComment($id);

            SupplierActivity::createActivity([
                'entity_id' => $note->id,
                'entity_type' => 'notes',
                'action_type' => 'delete_comment_note',
                'title' => $this->noteActivityTitle($note, ' - borttagen'),
                'description' => 'Togs bort.',
                'icon' => 'custom-cash',
                'route' => '/dashboard/admin/notes',
                'metadata' => json_encode([
                    'note_id' => $note->id,
                    'old_values' => $oldValues,
                ])
            ]);

            return response()->json([
                'success' => true
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    private function noteActivityTitle(Note $note, string $suffix): string
    {
        return "Vardering '{$this->noteActivityIdentifier($note)}' {$suffix}";
    }

    private function noteActivityIdentifier(Note $note): string
    {
        if (!empty($note->reg_num)) {
            return $note->reg_num;
        }

        if (!empty($note->name)) {
            return $note->name;
        }

        return '#' . $note->id;
    }
}
