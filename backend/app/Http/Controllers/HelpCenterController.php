<?php

namespace App\Http\Controllers;

use App\Http\Requests\HelpVideoRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\HelpVideo;
use App\Models\HelpCategory;

class HelpCenterController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view help-center|administrator')->only(['index', 'categories', 'category']);
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
     * Display the all categories.
     */
    public function categories()
    {
        try {

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $this->getHelpCategories(),
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

    public function category(Request $request, string $label): JsonResponse
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:newest,oldest,title',
        ]);

        try {
            $helpCategory = HelpCategory::where('label', $label)->where('state_id', 2)->first();

            if (! $helpCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategorin hittades inte.',
                ], 404);
            }

            $query = $helpCategory->videos()->where('state_id', 2)
                ->applyFilters(['search' => $filters['search'] ?? null]);

            $sort = $filters['sort'] ?? 'newest';
            if ($sort === 'title') {
                $query->orderBy('title')->orderBy('id');
            } else {
                $query->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc');
            }

            $helpVideos = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'helpCategory' => $helpCategory,
                    'helpVideos' => $helpVideos,
                    'helpVideosTotalCount' => $helpVideos->count(),
                    'categories' => $this->getHelpCategories(),
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active help categories with active videos, including their counts
     */
    public static function getHelpCategories()
    {
        $activeVideos = fn ($query) => $query->where('state_id', 2);

        return HelpCategory::where('state_id', 2)
            ->whereHas('videos', $activeVideos)
            ->withCount(['videos' => $activeVideos])
            ->get();
       
    }
}
