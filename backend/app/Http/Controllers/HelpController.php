<?php

namespace App\Http\Controllers;

use App\Http\Requests\HelpFilterRequest;
use App\Http\Requests\HelpVideoRequest;
use App\Models\HelpModule;
use App\Models\HelpVideo;
use App\Models\State;
use App\Models\SupplierActivity;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middlewares\PermissionMiddleware;

class HelpController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class.':view help|administrator')->only(['index', 'show']);
        $this->middleware(PermissionMiddleware::class.':create help|administrator')->only('store');
        $this->middleware(PermissionMiddleware::class.':edit help|administrator')->only(['update', 'updateState']);
        $this->middleware(PermissionMiddleware::class.':delete help|administrator')->only('destroy');
    }

    public function index(HelpFilterRequest $request): JsonResponse
    {
        return $this->listing($request, false);
    }

    public function center(HelpFilterRequest $request): JsonResponse
    {
        return $this->listing($request, true);
    }

    private function listing(HelpFilterRequest $request, bool $activeOnly): JsonResponse
    {
        try {
            $filters = $request->validated();
            if ($activeOnly) {
                unset($filters['state_id']);
            }
            $query = HelpVideo::with(['module:id,name,label,icon', 'state:id,name'])
                ->applyFilters($filters);
            if ($activeOnly) {
                $query->active();
            }
            $limit = (int) ($filters['limit'] ?? ($activeOnly ? 9 : 10));
            if ($limit === -1) {
                $all = $query->get();
                $videos = new LengthAwarePaginator($all, $all->count(), max($all->count(), 1), 1, [
                    'path' => $request->url(),
                ]);
            } else {
                $videos = $query->paginate($limit);
            }
            $videos->withQueryString();

            return response()->json([
                'success' => true,
                'data' => [
                    'videos' => $videos,
                    'modules' => HelpModule::orderBy('id')->get(),
                    'states' => $activeOnly ? [] : State::whereIn('id', [1, 2])->orderBy('id')->get(['id', 'name']),
                    'videosTotalCount' => $videos->total(),
                ],
            ]);
        } catch (QueryException $exception) {
            return $this->databaseError($exception);
        }
    }

    public function show(int $id): JsonResponse
    {
        $video = HelpVideo::with(['module', 'state'])->find($id);

        return $video
            ? response()->json(['success' => true, 'data' => ['video' => $video]])
            : $this->notFound();
    }

    public function store(HelpVideoRequest $request): JsonResponse
    {
        try {
            $video = DB::transaction(function () use ($request): HelpVideo {
                $video = HelpVideo::createVideo($request->validated());
                $this->activity($video, 'create', null);

                return $video;
            });

            return response()->json(['success' => true, 'data' => ['video' => $video->load(['module', 'state'])]]);
        } catch (QueryException $exception) {
            return $this->databaseError($exception);
        }
    }

    public function update(HelpVideoRequest $request, int $id): JsonResponse
    {
        $video = HelpVideo::find($id);
        if (! $video) {
            return $this->notFound();
        }

        try {
            DB::transaction(function () use ($request, $video): void {
                $old = $video->only($video->getFillable());
                HelpVideo::updateVideo($request->validated(), $video);
                $this->activity($video, 'update', $old);
            });

            return response()->json(['success' => true, 'data' => ['video' => $video->load(['module', 'state'])]]);
        } catch (QueryException $exception) {
            return $this->databaseError($exception);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $video = HelpVideo::find($id);
        if (! $video) {
            return $this->notFound();
        }

        try {
            DB::transaction(function () use ($video): void {
                $this->activity($video, 'delete', $video->only($video->getFillable()));
                HelpVideo::deleteVideo($video);
            });

            return response()->json(['success' => true, 'data' => ['video' => $video]]);
        } catch (QueryException $exception) {
            return $this->databaseError($exception);
        }
    }

    public function updateState(int $id): JsonResponse
    {
        $video = HelpVideo::find($id);
        if (! $video) {
            return $this->notFound();
        }

        try {
            DB::transaction(function () use ($video): void {
                $old = $video->only($video->getFillable());
                $video->update(['state_id' => $video->state_id === 2 ? 1 : 2]);
                $this->activity($video, 'update', $old);
            });

            return response()->json(['success' => true, 'data' => ['video' => $video->load(['module', 'state'])]]);
        } catch (QueryException $exception) {
            return $this->databaseError($exception);
        }
    }

    private function activity(HelpVideo $video, string $action, ?array $old): void
    {
        SupplierActivity::createActivity([
            'entity_id' => $video->id,
            'entity_type' => 'help',
            'action_type' => $action.'_help_video',
            'title' => $video->title,
            'description' => match ($action) {
                'create' => 'En hjälpvideo har lagts till.',
                'delete' => 'En hjälpvideo har tagits bort.',
                default => 'En hjälpvideo har uppdaterats.',
            },
            'icon' => 'custom-help',
            'route' => null,
            'metadata' => json_encode([
                'video_id' => $video->id,
                'old_values' => $old,
                'new_values' => $action === 'delete' ? null : $video->only($video->getFillable()),
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false, 'feedback' => 'not_found', 'message' => 'Hjälpvideon hittades inte',
        ], 404);
    }

    private function databaseError(QueryException $exception): JsonResponse
    {
        report($exception);

        return response()->json(['success' => false, 'message' => 'database_error'], 500);
    }
}
