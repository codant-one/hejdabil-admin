<?php

namespace App\Http\Controllers;

use App\Http\Requests\HelpVideoRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\HelpVideo;
use App\Models\HelpCategory;
use App\Models\State;

class HelpVideoController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view help-videos|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create help-videos|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit help-videos|administrator')->only(['update', 'updateState']);
        $this->middleware(PermissionMiddleware::class . ':delete help-videos|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = HelpVideo::with(['state:id,name', 'category:id,name,label'])->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'state_id',
                                    'help_category_id'
                                ])
                            );

            if ($limit == -1) {
                $allHelpVideos = $query->get();
                $helpVideos = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allHelpVideos,
                    $allHelpVideos->count(),
                    max($allHelpVideos->count(), 1),
                    1
                );
            } else {
                $helpVideos = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'helpVideos' => $helpVideos,
                    'helpVideosTotalCount' => $helpVideos->total(),
                    'categories' => HelpCategory::where('state_id', 2)->orderBy('id')->get(['id', 'name', 'label', 'state_id']),
                    'states' => State::whereIn('id', [1, 2])->orderBy('id')->get(['id', 'name'])
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
    public function store(HelpVideoRequest $request)
    {
        try {

            $helpVideo = HelpVideo::createHelpVideo($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpVideo' => $helpVideo->load(['category:id,name,label', 'state:id,name'])
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

            $helpVideo = HelpVideo::find($id);

            if (!$helpVideo)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Videon hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpVideo' => $helpVideo->load(['category:id,name,label', 'state:id,name'])
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
    public function update(HelpVideoRequest $request, $id): JsonResponse
    {
        try {
            $helpVideo = HelpVideo::find($id);
        
            if (!$helpVideo)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Videon hittades inte'
                ], 404);

            $helpVideo->updateHelpVideo($request, $helpVideo); 
            
            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpVideo' => $helpVideo->load(['category:id,name,label', 'state:id,name'])
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

            $helpVideo = HelpVideo::find($id);
        
            if (!$helpVideo)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Videon hittades inte'
                ], 404);
            
            $helpVideo->deleteHelpVideo($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'helpVideo' => $helpVideo
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

    public function updateState($id): JsonResponse
    {
        try {
            $helpVideo = HelpVideo::find($id);

            if (!$helpVideo) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Videon hittades inte'
                ], 404);
            }

            $helpVideo->state_id = $helpVideo->state_id === 1 ? 2 : 1;
            $helpVideo->update();

            return response()->json([
                'success' => true,
                'data' => [
                    'helpVideo' => $helpVideo->load(['category:id,name,label', 'state:id,name'])
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
}
