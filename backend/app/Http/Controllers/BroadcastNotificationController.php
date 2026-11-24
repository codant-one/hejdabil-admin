<?php

namespace App\Http\Controllers;

use App\Http\Requests\BroadcastNotificationRequest;
use App\Models\AdminNotification;
use App\Services\BroadcastNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Middlewares\PermissionMiddleware;

class BroadcastNotificationController extends Controller
{
    public function __construct(
        private readonly BroadcastNotificationService $broadcasts
    ) {
        // Same pattern as RoleController: module permission or the "administrator" permission.
        $this->middleware('jwt');
        $this->middleware(PermissionMiddleware::class . ':view notifications|create notifications|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create notifications|administrator')->only(['roles', 'users', 'previewCount', 'store']);
        $this->middleware(PermissionMiddleware::class . ':delete notifications|administrator')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 10), 1), 100);

        $history = AdminNotification::query()
            ->with(['user:id,name,last_name,email', 'state:id,name,label'])
            ->latest('id')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'broadcasts' => $history,
                'broadcastsTotalCount' => $history->total(),
            ],
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $this->broadcasts->availableRoles(),
                'all_count' => $this->broadcasts->countRecipients(AdminNotification::AUDIENCE_ALL),
            ],
        ]);
    }

    public function previewCount(BroadcastNotificationRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'recipients_count' => $this->broadcasts->countRecipients(
                    $request->validated('audience'),
                    $request->validated('roles', []),
                    $request->validated('user_ids', [])
                ),
            ],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $includeIds = array_filter((array) $request->input('include', []), 'is_numeric');

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $this->broadcasts->searchUsers($request->input('search'), $includeIds),
            ],
        ]);
    }

    public function store(BroadcastNotificationRequest $request): JsonResponse
    {
        try {
            $broadcast = $this->broadcasts->send($request->validated(), Auth::user());
        } catch (\Throwable $exception) {
            Log::error('Failed to queue broadcast notification', ['error' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Notisen kunde inte skickas. Försök igen.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notisen har köats för utskick',
            'data' => [
                'broadcast' => $broadcast->fresh(['state:id,name,label']),
            ],
        ], 201);
    }

    public function destroy($id): JsonResponse
    {
        $broadcast = AdminNotification::query()->find($id);

        if (! $broadcast) {
            return response()->json([
                'success' => false,
                'feedback' => 'not_found',
                'message' => 'Notisen hittades inte',
            ], 404);
        }

        // Chunk jobs may still be creating notifications for an in-progress broadcast.
        if ($broadcast->hasState(AdminNotification::STATE_QUEUED) || $broadcast->hasState(AdminNotification::STATE_SENDING)) {
            return response()->json([
                'success' => false,
                'feedback' => 'in_progress',
                'message' => 'Notisen skickas fortfarande och kan inte tas bort ännu.',
            ], 409);
        }

        try {
            $deletedNotifications = $this->broadcasts->delete($broadcast);
        } catch (\Throwable $exception) {
            Log::error('Failed to delete broadcast notification', [
                'broadcast_id' => $broadcast->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notisen kunde inte tas bort. Försök igen.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notisen har tagits bort',
            'data' => [
                'id' => (int) $id,
                'deleted_notifications' => $deletedNotifications,
            ],
        ]);
    }
}
