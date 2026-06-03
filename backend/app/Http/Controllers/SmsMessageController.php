<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SmsMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 10);

            if ($limit !== -1) {
                $limit = max(1, $limit);
            }

            $user = Auth::user();
            $scope = $this->resolveScope($request, $user);

            $summaryQuery = SmsMessage::query();
            $this->applyScope($summaryQuery, $scope);
            $this->applyCommonFilters($summaryQuery, $request);

            $summary = (clone $summaryQuery)
                ->selectRaw('
                    COUNT(*) as total_count,
                    SUM(CASE WHEN billable_count > 0 THEN 1 ELSE 0 END) as accepted_count,
                    SUM(CASE WHEN billable_count = 0 THEN 1 ELSE 0 END) as failed_count,
                    SUM(CASE WHEN supplier_id IS NOT NULL THEN 1 ELSE 0 END) as with_supplier_count,
                    SUM(CASE WHEN supplier_id IS NULL THEN 1 ELSE 0 END) as without_supplier_count
                ')
                ->first();

            $query = SmsMessage::with([
                'supplier' => function ($supplierQuery) {
                    $supplierQuery->select('id', 'user_id', 'boss_id', 'deleted_at')
                        ->withTrashed()
                        ->with([
                            'user' => function ($userQuery) {
                                $userQuery->select('id', 'name', 'last_name', 'email', 'deleted_at')
                                    ->withTrashed()
                                    ->with(['userDetail:user_id,company']);
                            },
                        ]);
                },
                'user' => function ($userQuery) {
                    $userQuery->select('id', 'name', 'last_name', 'email', 'deleted_at')
                        ->withTrashed()
                        ->with(['userDetail:user_id,company']);
                },
            ]);

            $this->applyScope($query, $scope);
            $this->applyCommonFilters($query, $request);
            $this->applyStatusFilter($query, $request->input('status'));

            $query->orderByDesc('created_at')->orderByDesc('id');

            if ($limit === -1) {
                $allMessages = $query->get();
                $perPage = max(1, $allMessages->count());
                $smsMessages = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allMessages,
                    $allMessages->count(),
                    $perPage,
                    1
                );
            } else {
                $smsMessages = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'smsMessages' => $smsMessages,
                    'smsMessagesTotalCount' => $smsMessages->total(),
                    'summary' => [
                        'total_count' => (int) ($summary->total_count ?? 0),
                        'accepted_count' => (int) ($summary->accepted_count ?? 0),
                        'failed_count' => (int) ($summary->failed_count ?? 0),
                        'with_supplier_count' => (int) ($summary->with_supplier_count ?? 0),
                        'without_supplier_count' => (int) ($summary->without_supplier_count ?? 0),
                    ],
                    'meta' => [
                        'scope' => $scope['type'],
                        'supplier_id' => $scope['supplier_id'] ?? null,
                    ],
                ],
            ]);
        } catch (\Throwable $ex) {
            return response()->json([
                'success' => false,
                'message' => 'server_error',
                'exception' => $ex->getMessage(),
            ], 500);
        }
    }

    private function resolveScope(Request $request, $user): array
    {
        if (!$user) {
            return ['type' => 'without_supplier'];
        }

        $user->loadMissing('supplier.boss');

        if ($user->hasRole('Supplier')) {
            return [
                'type' => 'supplier_account',
                'supplier_id' => $user->supplier?->id,
            ];
        }

        if ($user->hasRole('User')) {
            return [
                'type' => 'supplier_account',
                'supplier_id' => $user->supplier?->boss_id ?? $user->supplier?->id,
            ];
        }

        if ($user->hasAnyRole(['SuperAdmin', 'Administrator'])) {
            $supplierScope = trim((string) $request->input('supplier_scope', 'all'));

            return match ($supplierScope) {
                'with_supplier' => ['type' => 'with_supplier'],
                'without_supplier' => ['type' => 'without_supplier'],
                default => ['type' => 'all'],
            };
        }

        return ['type' => 'without_supplier'];
    }

    private function applyScope($query, array $scope): void
    {
        if (($scope['type'] ?? null) === 'supplier_account') {
            $supplierId = $scope['supplier_id'] ?? null;

            if ($supplierId !== null) {
                $query->where('supplier_id', $supplierId);
                return;
            }

            $query->whereRaw('1 = 0');
            return;
        }

        if (($scope['type'] ?? null) === 'with_supplier') {
            $query->whereNotNull('supplier_id');
            return;
        }

        if (($scope['type'] ?? null) === 'without_supplier') {
            $query->whereNull('supplier_id');
        }
    }

    private function applyCommonFilters($query, Request $request): void
    {
        $dateFrom = $this->resolveDateBoundary($request->input('date_from'), false);
        $dateTo = $this->resolveDateBoundary($request->input('date_to'), true);

        if ($dateFrom !== null) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->where('created_at', '<=', $dateTo);
        }

        if ($dateFrom !== null || $dateTo !== null) {
            return;
        }

        $billingMonth = $this->resolveBillingMonth($request->input('billing_month'));

        if ($billingMonth !== null) {
            $query->forBillingMonth($billingMonth);
        }
    }

    private function applyStatusFilter($query, $status): void
    {
        $normalizedStatus = trim((string) $status);

        if ($normalizedStatus === 'accepted') {
            $query->where('billable_count', '>', 0);
            return;
        }

        if ($normalizedStatus === 'failed') {
            $query->where('billable_count', '=', 0);
        }
    }

    private function resolveBillingMonth($value): ?string
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $normalizedValue) !== 1) {
            return null;
        }

        return Carbon::createFromFormat('Y-m', $normalizedValue)->startOfMonth()->toDateString();
    }

    private function resolveDateBoundary($value, bool $endOfDay): ?Carbon
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalizedValue) !== 1) {
            return null;
        }

        $resolvedDate = Carbon::createFromFormat('Y-m-d', $normalizedValue);

        return $endOfDay
            ? $resolvedDate->endOfDay()
            : $resolvedDate->startOfDay();
    }
}