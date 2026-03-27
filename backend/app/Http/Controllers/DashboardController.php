<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

use App\Models\Agreement;
use App\Models\Billing;
use App\Models\Payout;
use App\Models\Reminder;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\VehicleTask;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function statisticians(Request $request): JsonResponse
    {
        try {

            //Filters dates
            $today = Carbon::today();
            $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');
            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
            $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

            if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            $defaultRangeStart = $hasDateFilter
                ? $today->copy()->subMonthsNoOverflow(11)->startOfMonth()
                : $today->copy()->startOfYear();
            $defaultRangeEnd = $hasDateFilter
                ? $today->copy()->endOfDay()
                : $today->copy()->endOfYear();

            $filterStart = ($dateFrom ?: $defaultRangeStart)->copy()->startOfDay();
            $filterEnd = ($dateTo ?: $defaultRangeEnd)->copy()->endOfDay();

            $chartStartMonth = $filterStart->copy()->startOfMonth();
            $chartEndMonth = $filterEnd->copy()->startOfMonth();

            $supplierId = $this->getCurrentSupplierId();

            $vehiclePriceSummary = $this->getVehiclePriceSummaryByMonth(
                $supplierId,
                $filterStart,
                $filterEnd,
                $chartStartMonth,
                $chartEndMonth,
            );

            $totalPurchasePrice = $vehiclePriceSummary['totalPurchasePrice'];
            $totalSalePrice = $vehiclePriceSummary['totalSalePrice'];
            $totalCost = $vehiclePriceSummary['totalCost'];
            $totalProfit = $vehiclePriceSummary['totalProfit'];

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicles' => Vehicle::query()->where('supplier_id', $supplierId)->count(),
                    'priceByMonth' => $vehiclePriceSummary['months'],
                    'totalPurchasePrice' => $totalPurchasePrice,
                    'totalPurchasePriceAbbreviated' => $this->formatSwedishAbbreviatedCurrency($totalPurchasePrice),
                    'totalSalePrice' => $totalSalePrice,
                    'totalSalePriceAbbreviated' => $this->formatSwedishAbbreviatedCurrency($totalSalePrice),
                    'totalCost' => $totalCost,
                    'totalCostAbbreviated' => $this->formatSwedishAbbreviatedCurrency($totalCost),
                    'totalProfit' => $totalProfit,
                    'totalProfitAbbreviated' => $this->formatSwedishAbbreviatedCurrency($totalProfit),
                    'dateRange' => [
                        'year' => $chartStartMonth->year,
                        'year_from' => $chartStartMonth->year,
                        'year_to' => $chartEndMonth->year,
                        'date_from' => $filterStart->toDateString(),
                        'date_to' => $filterEnd->toDateString(),
                    ]
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

    public function indicators(Request $request): JsonResponse
    {
        try {
            $supplierId = $this->getCurrentSupplierId();
            [$hasDateFilter, $dateFrom, $dateTo] = $this->resolveDateFilters($request);

            $filterStart = $hasDateFilter
                ? ($dateFrom ?: Carbon::today()->copy()->subMonthsNoOverflow(11)->startOfMonth())->copy()->startOfDay()
                : null;
            $filterEnd = $hasDateFilter
                ? ($dateTo ?: Carbon::today()->copy()->endOfDay())->copy()->endOfDay()
                : null;
            $comparisonMonth = ($filterEnd ?: Carbon::today()->copy())->copy()->startOfMonth();

            $stockVehiclesBaseQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->where('state_id', 10);
            $purchasedVehiclesBaseQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->whereNotNull('purchase_price')
                ->whereNotNull('purchase_date');
            $soldVehiclesBaseQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->where('state_id', 12)
                ->whereNotNull('sale_price')
                ->whereNotNull('sale_date');

            $stockIndicator = $this->buildIndicatorSummary(
                $stockVehiclesBaseQuery,
                'purchase_date',
                'purchase_price',
                $comparisonMonth,
                $filterStart,
                $filterEnd,
            );
            $purchasedIndicator = $this->buildIndicatorSummary(
                $purchasedVehiclesBaseQuery,
                'purchase_date',
                'purchase_price',
                $comparisonMonth,
                $filterStart,
                $filterEnd,
            );
            $soldIndicator = $this->buildIndicatorSummary(
                $soldVehiclesBaseQuery,
                'sale_date',
                'sale_price',
                $comparisonMonth,
                $filterStart,
                $filterEnd,
            );

            $stockVehiclesPurchasePrice = $stockIndicator['totalPrice'];
            $purchasedVehiclesPrice = $purchasedIndicator['totalPrice'];
            $soldVehiclesPrice = $soldIndicator['totalPrice'];

            return response()->json([
                'success' => true,
                'data' => [
                    'vehiclesInStock' => $stockIndicator['count'],
                    'stockVehiclesPurchasePrice' => $stockVehiclesPurchasePrice,
                    'stockVehiclesPurchasePriceAbbreviated' => $this->formatSwedishAbbreviatedCurrency($stockVehiclesPurchasePrice),
                    'stockVehiclesMonthlyVariation' => $stockIndicator['monthlyVariation'],
                    'purchasedVehiclesCount' => $purchasedIndicator['count'],
                    'purchasedVehiclesPrice' => $purchasedVehiclesPrice,
                    'purchasedVehiclesPriceAbbreviated' => $this->formatSwedishAbbreviatedCurrency($purchasedVehiclesPrice),
                    'purchasedVehiclesMonthlyVariation' => $purchasedIndicator['monthlyVariation'],
                    'soldVehiclesCount' => $soldIndicator['count'],
                    'soldVehiclesPrice' => $soldVehiclesPrice,
                    'soldVehiclesPriceAbbreviated' => $this->formatSwedishAbbreviatedCurrency($soldVehiclesPrice),
                    'soldVehiclesMonthlyVariation' => $soldIndicator['monthlyVariation'],
                    'dateRange' => [
                        'date_from' => $filterStart?->toDateString(),
                        'date_to' => $filterEnd?->toDateString(),
                    ],
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

    public function profit(): JsonResponse
    {
        try {
            $supplierId = $this->getCurrentSupplierId();
            $comparisonMonth = Carbon::today()->copy()->startOfMonth();

            $stockVehiclesBaseQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->where('state_id', 10);
            $soldVehiclesBaseQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->where('state_id', 12)
                ->whereNotNull('sale_price')
                ->whereNotNull('sale_date');
            $taskCostBaseQuery = VehicleTask::query()
                ->where('is_cost', 1)
                ->whereHas('vehicle', function ($query) use ($supplierId) {
                    $query->where('supplier_id', $supplierId);
                });

            $totalSale = $this->getFilteredQuerySum(
                $soldVehiclesBaseQuery,
                'sale_date',
                'sale_price',
                null,
                null,
            );
            $totalPurchase = $this->getFilteredQuerySum(
                $stockVehiclesBaseQuery,
                'purchase_date',
                'purchase_price',
                null,
                null,
            );
            $totalCost = $this->getFilteredQuerySum(
                $taskCostBaseQuery,
                'updated_at',
                'cost',
                null,
                null,
            );

            $totalProfit = round((float) ($totalSale - $totalPurchase - $totalCost), 2);
            $saleMonthlyVariation = $this->getMonthlySumVariationForQuery(
                $soldVehiclesBaseQuery,
                'sale_date',
                'sale_price',
                $comparisonMonth,
            );
            $profitMonthlyVariation = $this->getProfitMonthlyVariation(
                $soldVehiclesBaseQuery,
                $stockVehiclesBaseQuery,
                $taskCostBaseQuery,
                $comparisonMonth,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicles' => Vehicle::query()->where('supplier_id', $supplierId)->count(),
                    'totalSale' => $totalSale,
                    'totalSaleAbbreviated' => $this->formatSwedishAbbreviatedCurrency($totalSale),
                    'totalSaleMonthlyVariation' => $saleMonthlyVariation,
                    'totalProfit' => $totalProfit,
                    'totalProfitAbbreviated' => $this->formatSwedishAbbreviatedCurrency($totalProfit),
                    'totalProfitMonthlyVariation' => $profitMonthlyVariation,
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

    public function measures(): JsonResponse
    {
        try {
            $supplierId = $this->getCurrentSupplierId();
            $measures = VehicleTask::query()
                ->with([
                    'vehicle:id,reg_num,state_id,model_id,year',
                    'vehicle.model:id,name,brand_id',
                    'vehicle.model.brand:id,name'
                ])
                ->where('is_cost', 0)                
                ->whereHas('vehicle', function ($query) use ($supplierId) {
                    $query->where('supplier_id', $supplierId)
                          ->where('state_id', 10);
                })
                ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('start_date')
                ->orderByDesc('id')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'measures' => $measures,
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

    public function team(Request $request): JsonResponse
    {
        try {
            $supplierId = $this->getCurrentSupplierId();
            [$hasDateFilter, $dateFrom, $dateTo] = $this->resolveDateFilters($request);

            $filterStart = $hasDateFilter
                ? ($dateFrom ?: Carbon::today()->copy()->subMonthsNoOverflow(11)->startOfMonth())->copy()->startOfDay()
                : null;
            $filterEnd = $hasDateFilter
                ? ($dateTo ?: Carbon::today()->copy()->endOfDay())->copy()->endOfDay()
                : null;

            $teamSuppliers = Supplier::query()
                ->with(['user.userDetail'])
                ->where('boss_id', $supplierId)
                ->orderBy('order_id')
                ->orderBy('id')
                ->get();

            $teamUserIds = $teamSuppliers
                ->pluck('user_id')
                ->filter()
                ->values()
                ->all();

            $billingCountsByUser = $this->getTeamDocumentCountsByUser(
                Billing::query()->where('supplier_id', $supplierId),
                $teamUserIds,
                $filterStart,
                $filterEnd,
            );
            $payoutCountsByUser = $this->getTeamDocumentCountsByUser(
                Payout::query()->where('supplier_id', $supplierId),
                $teamUserIds,
                $filterStart,
                $filterEnd,
            );
            $agreementCountsByUser = $this->getTeamDocumentCountsByUser(
                Agreement::query()->where('supplier_id', $supplierId),
                $teamUserIds,
                $filterStart,
                $filterEnd,
            );

            $teamMembers = $teamSuppliers->map(function ($teamSupplier) use (
                $billingCountsByUser,
                $payoutCountsByUser,
                $agreementCountsByUser,
            ) {
                $user = $teamSupplier->user;
                $userId = $teamSupplier->user_id;
                $invoices = (int) ($billingCountsByUser->get($userId, 0));
                $swish = (int) ($payoutCountsByUser->get($userId, 0));
                $agreements = (int) ($agreementCountsByUser->get($userId, 0));

                return [
                    'id' => $user?->id,
                    'supplier_id' => $teamSupplier->id,
                    'name' => $user?->name,
                    'last_name' => $user?->last_name,
                    'email' => $user?->email,
                    'avatar' => $user?->avatar,
                    'user_detail' => $user?->userDetail,
                    'invoices' => $invoices,
                    'swish' => $swish,
                    'agreements' => $agreements,
                    'total_actions' => $invoices + $swish + $agreements,
                ];
            })
                ->sortByDesc('total_actions')
                ->take(5)
                ->values()
                ->map(function ($teamMember) {
                    unset($teamMember['total_actions']);

                    return $teamMember;
                });

            $totalBillings = $this->getTeamDocumentTotalCount(
                Billing::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );
            $totalPayouts = $this->getTeamDocumentTotalCount(
                Payout::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );
            $totalAgreements = $this->getTeamDocumentTotalCount(
                Agreement::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'teamMembers' => $teamMembers,
                    'teamTotals' => [
                        'billings' => $totalBillings,
                        'payouts' => $totalPayouts,
                        'agreements' => $totalAgreements,
                    ],
                    'dateRange' => [
                        'date_from' => $filterStart?->toDateString(),
                        'date_to' => $filterEnd?->toDateString(),
                    ],
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

    public function vehicles(Request $request): JsonResponse
    {
        try {
            $supplierId = $this->getCurrentSupplierId();
            [$hasDateFilter, $dateFrom, $dateTo] = $this->resolveDateFilters($request);
            $sortBy = $request->input('sort_by', 'latest_added');

            $filterStart = $hasDateFilter
                ? ($dateFrom ?: Carbon::today()->copy()->subMonthsNoOverflow(11)->startOfMonth())->copy()->startOfDay()
                : null;
            $filterEnd = $hasDateFilter
                ? ($dateTo ?: Carbon::today()->copy()->endOfDay())->copy()->endOfDay()
                : null;

            $stockVehicles = $this->getDashboardVehiclesByState(
                10,
                $supplierId,
                $filterStart,
                $filterEnd,
                $sortBy,
            );

            $soldVehicles = $this->getDashboardVehiclesByState(
                12,
                $supplierId,
                $filterStart,
                $filterEnd,
                $sortBy,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'stockVehicles' => $stockVehicles,
                    'soldVehicles' => $soldVehicles,
                    'dateRange' => [
                        'date_from' => $filterStart?->toDateString(),
                        'date_to' => $filterEnd?->toDateString(),
                    ],
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

    public function reminders(): JsonResponse
    {
        try {
            $reminders = Reminder::query()
                ->with(['user'])
                ->where('user_id', Auth::id())
                ->orderBy('is_done')
                ->orderBy('start_date')
                ->orderBy('end_date')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'reminders' => $reminders,
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

    private function getVehiclePriceSummaryByMonth(
        int $supplierId,
        Carbon $filterStart,
        Carbon $filterEnd,
        Carbon $chartStartMonth,
        Carbon $chartEndMonth,
    ): array {
        $purchasePriceByMonth = $this->getMonthlyVehicleTotals(
            'purchase_date',
            'purchase_price',
            'total_purchase_price',
            10,
            $supplierId,
            $filterStart,
            $filterEnd,
        );

        $salePriceByMonth = $this->getMonthlyVehicleTotals(
            'sale_date',
            'sale_price',
            'total_sale_price',
            12,
            $supplierId,
            $filterStart,
            $filterEnd,
        );

        $taskCostByMonth = $this->getMonthlyVehicleTaskCosts(
            $supplierId,
            $filterStart,
            $filterEnd,
        );

        $totalsByMonth = $purchasePriceByMonth->mapWithKeys(function ($item) {
            return [
                $item->month => round((float) $item->total_purchase_price, 2),
            ];
        });

        $totalsSaleByMonth = $salePriceByMonth->mapWithKeys(function ($item) {
            return [
                $item->month => round((float) $item->total_sale_price, 2),
            ];
        });

        $totalsTaskCostByMonth = $taskCostByMonth->mapWithKeys(function ($item) {
            return [
                $item->month => round((float) $item->total_cost, 2),
            ];
        });

        $months = [];
        $cursor = $chartStartMonth->copy();

        while ($cursor->lte($chartEndMonth)) {
            $monthKey = $cursor->format('Y-m');
            $totalPurchasePrice = $totalsByMonth->get($monthKey, 0);
            $totalSalePrice = $totalsSaleByMonth->get($monthKey, 0);
            $totalCost = $totalsTaskCostByMonth->get($monthKey, 0);
            $totalProfit = round((float) ($totalSalePrice - $totalPurchasePrice - $totalCost), 2);

            $months[] = [
                'month' => $monthKey,
                'month_label' => $cursor->format('M y'),
                'total_purchase_price' => $totalPurchasePrice,
                'total_purchase_price_abbreviated' => $this->formatSwedishAbbreviatedCurrency($totalPurchasePrice),
                'total_sale_price' => $totalSalePrice,
                'total_sale_price_abbreviated' => $this->formatSwedishAbbreviatedCurrency($totalSalePrice),
                'total_cost' => $totalCost,
                'total_cost_abbreviated' => $this->formatSwedishAbbreviatedCurrency($totalCost),
                'total_profit' => $totalProfit,
                'total_profit_abbreviated' => $this->formatSwedishAbbreviatedCurrency($totalProfit),
            ];

            $cursor->addMonth();
        }

        $totalPurchasePrice = round((float) $totalsByMonth->sum(), 2);
        $totalSalePrice = round((float) $totalsSaleByMonth->sum(), 2);
        $totalCost = round((float) $totalsTaskCostByMonth->sum(), 2);

        return [
            'months' => $months,
            'totalPurchasePrice' => $totalPurchasePrice,
            'totalSalePrice' => $totalSalePrice,
            'totalCost' => $totalCost,
            'totalProfit' => round((float) ($totalSalePrice - $totalPurchasePrice - $totalCost), 2),
        ];
    }

    private function getMonthlyVehicleTotals(
        string $dateField,
        string $priceField,
        string $totalAlias,
        int $stateId,
        int $supplierId,
        Carbon $filterStart,
        Carbon $filterEnd,
    ) {
        return Vehicle::query()
            ->selectRaw("DATE_FORMAT($dateField, '%Y-%m') as month")
            ->selectRaw("SUM($priceField) as $totalAlias")
            ->whereNotNull($dateField)
            ->whereNotNull($priceField)
            ->whereBetween($dateField, [
                $filterStart->toDateString(),
                $filterEnd->toDateString(),
            ])
            ->where('supplier_id', $supplierId)
            ->where('state_id', $stateId)
            ->groupBy(DB::raw("DATE_FORMAT($dateField, '%Y-%m')"))
            ->orderBy('month')
            ->get();
    }

    private function getMonthlyVehicleTaskCosts(
        int $supplierId,
        Carbon $filterStart,
        Carbon $filterEnd,
    ) {
        return VehicleTask::query()
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as month")
            ->selectRaw('SUM(cost) as total_cost')
            ->whereNotNull('updated_at')
            ->whereNotNull('cost')
            ->whereBetween('updated_at', [
                $filterStart->toDateString(),
                $filterEnd->toDateString(),
            ])
            ->where('is_cost', 1)
            ->whereHas('vehicle', function ($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->groupBy(DB::raw("DATE_FORMAT(updated_at, '%Y-%m')"))
            ->orderBy('month')
            ->get();
    }

    private function resolveDateFilters(Request $request): array
    {
        $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$hasDateFilter, $dateFrom, $dateTo];
    }

    private function buildIndicatorSummary(
        $baseQuery,
        string $dateField,
        string $priceField,
        Carbon $comparisonMonth,
        ?Carbon $filterStart,
        ?Carbon $filterEnd,
    ): array {
        $filteredQuery = $this->applyDateFilterToQuery($baseQuery, $dateField, $filterStart, $filterEnd);

        return [
            'count' => (clone $filteredQuery)->count(),
            'totalPrice' => round((float) (clone $filteredQuery)->sum($priceField), 2),
            'monthlyVariation' => $this->getMonthlyVariationForQuery(
                $baseQuery,
                $dateField,
                $comparisonMonth,
            ),
        ];
    }

    private function applyDateFilterToQuery($query, string $dateField, ?Carbon $filterStart, ?Carbon $filterEnd)
    {
        $scopedQuery = clone $query;

        if (!$filterStart || !$filterEnd) {
            return $scopedQuery;
        }

        return $scopedQuery
            ->whereNotNull($dateField)
            ->whereBetween($dateField, [
                $filterStart->toDateString(),
                $filterEnd->toDateString(),
            ]);
    }

    private function applyDateTimeFilterToQuery($query, string $dateField, ?Carbon $filterStart, ?Carbon $filterEnd)
    {
        $scopedQuery = clone $query;

        if (!$filterStart || !$filterEnd) {
            return $scopedQuery;
        }

        return $scopedQuery
            ->whereNotNull($dateField)
            ->whereBetween($dateField, [
                $filterStart->toDateTimeString(),
                $filterEnd->toDateTimeString(),
            ]);
    }

    private function getTeamDocumentCountsByUser(
        $query,
        array $userIds,
        ?Carbon $filterStart,
        ?Carbon $filterEnd,
    ) {
        if (empty($userIds)) {
            return collect();
        }

        $filteredQuery = $this->applyDateTimeFilterToQuery($query, 'created_at', $filterStart, $filterEnd);

        return (clone $filteredQuery)
            ->whereNotNull('user_id')
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');
    }

    private function getTeamDocumentTotalCount(
        $query,
        ?Carbon $filterStart,
        ?Carbon $filterEnd,
    ): int {
        $filteredQuery = $this->applyDateTimeFilterToQuery($query, 'created_at', $filterStart, $filterEnd);

        return (clone $filteredQuery)->count();
    }

    private function getQueryCountByDateRange(
        $query,
        string $dateField,
        Carbon $startDate,
        Carbon $endDate,
    ): int {
        return (clone $query)
            ->whereNotNull($dateField)
            ->whereBetween($dateField, [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->count();
    }

    private function getQuerySumByDateRange(
        $query,
        string $dateField,
        string $sumField,
        Carbon $startDate,
        Carbon $endDate,
    ): float {
        return round((float) (clone $query)
            ->whereNotNull($dateField)
            ->whereNotNull($sumField)
            ->whereBetween($dateField, [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->sum($sumField), 2);
    }

    private function getFilteredQuerySum(
        $query,
        string $dateField,
        string $sumField,
        ?Carbon $filterStart,
        ?Carbon $filterEnd,
    ): float {
        $filteredQuery = $this->applyDateFilterToQuery($query, $dateField, $filterStart, $filterEnd);

        return round((float) (clone $filteredQuery)
            ->whereNotNull($sumField)
            ->sum($sumField), 2);
    }

    private function getMonthlyVariationForQuery($query, string $dateField, Carbon $comparisonMonth): float
    {
        $currentMonthStart = $comparisonMonth->copy()->startOfMonth();
        $currentMonthEnd = $comparisonMonth->copy()->endOfMonth();
        $previousMonthStart = $comparisonMonth->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $comparisonMonth->copy()->subMonth()->endOfMonth();

        $currentMonthCount = $this->getQueryCountByDateRange(
            $query,
            $dateField,
            $currentMonthStart,
            $currentMonthEnd,
        );

        $previousMonthCount = $this->getQueryCountByDateRange(
            $query,
            $dateField,
            $previousMonthStart,
            $previousMonthEnd,
        );

        return $this->calculatePercentageChange($previousMonthCount, $currentMonthCount);
    }

    private function getMonthlySumVariationForQuery(
        $query,
        string $dateField,
        string $sumField,
        Carbon $comparisonMonth,
    ): float {
        $currentMonthStart = $comparisonMonth->copy()->startOfMonth();
        $currentMonthEnd = $comparisonMonth->copy()->endOfMonth();
        $previousMonthStart = $comparisonMonth->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $comparisonMonth->copy()->subMonth()->endOfMonth();

        $currentMonthSum = $this->getQuerySumByDateRange(
            $query,
            $dateField,
            $sumField,
            $currentMonthStart,
            $currentMonthEnd,
        );

        $previousMonthSum = $this->getQuerySumByDateRange(
            $query,
            $dateField,
            $sumField,
            $previousMonthStart,
            $previousMonthEnd,
        );

        return $this->calculatePercentageChange((int) round($previousMonthSum), (int) round($currentMonthSum));
    }

    private function getProfitMonthlyVariation(
        $soldVehiclesBaseQuery,
        $stockVehiclesBaseQuery,
        $taskCostBaseQuery,
        Carbon $comparisonMonth,
    ): float {
        $currentMonthStart = $comparisonMonth->copy()->startOfMonth();
        $currentMonthEnd = $comparisonMonth->copy()->endOfMonth();
        $previousMonthStart = $comparisonMonth->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $comparisonMonth->copy()->subMonth()->endOfMonth();

        $currentProfit = $this->calculateProfitForDateRange(
            $soldVehiclesBaseQuery,
            $stockVehiclesBaseQuery,
            $taskCostBaseQuery,
            $currentMonthStart,
            $currentMonthEnd,
        );

        $previousProfit = $this->calculateProfitForDateRange(
            $soldVehiclesBaseQuery,
            $stockVehiclesBaseQuery,
            $taskCostBaseQuery,
            $previousMonthStart,
            $previousMonthEnd,
        );

        return $this->calculatePercentageChange((int) round($previousProfit), (int) round($currentProfit));
    }

    private function calculateProfitForDateRange(
        $soldVehiclesBaseQuery,
        $stockVehiclesBaseQuery,
        $taskCostBaseQuery,
        Carbon $startDate,
        Carbon $endDate,
    ): float {
        $totalSale = $this->getQuerySumByDateRange(
            $soldVehiclesBaseQuery,
            'sale_date',
            'sale_price',
            $startDate,
            $endDate,
        );
        $totalPurchase = $this->getQuerySumByDateRange(
            $stockVehiclesBaseQuery,
            'purchase_date',
            'purchase_price',
            $startDate,
            $endDate,
        );
        $totalCost = $this->getQuerySumByDateRange(
            $taskCostBaseQuery,
            'updated_at',
            'cost',
            $startDate,
            $endDate,
        );

        return round((float) ($totalSale - $totalPurchase - $totalCost), 2);
    }

    private function calculatePercentageChange(int $previousValue, int $currentValue): float
    {
        if ($previousValue === 0) {
            return $currentValue > 0 ? 100.0 : 0.0;
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, 2);
    }

    private function formatSwedishAbbreviatedCurrency(float $amount): string
    {
        $absoluteAmount = abs($amount);

        if ($absoluteAmount >= 1000000000) {
            return $this->formatSwedishAbbreviatedNumber($amount, 1000000000, 'Md');
        }

        if ($absoluteAmount >= 1000000) {
            return $this->formatSwedishAbbreviatedNumber($amount, 1000000, 'M');
        }

        if ($absoluteAmount >= 1000) {
            return $this->formatSwedishAbbreviatedNumber($amount, 1000, 'k');
        }

        $roundedAmount = round($amount, 1);
        $decimals = $this->hasFractionalComponent($roundedAmount) ? 1 : 0;

        return number_format($roundedAmount, $decimals, ',', ' ');
    }

    private function formatSwedishAbbreviatedNumber(float $amount, int $divisor, string $suffix): string
    {
        $scaledAmount = $amount / $divisor;
        $roundedAmount = round($scaledAmount, 1);
        $shouldKeepDecimal = $this->hasFractionalComponent($roundedAmount)
            || ($this->hasFractionalComponent($scaledAmount) && abs($roundedAmount) < 10);

        return number_format($roundedAmount, $shouldKeepDecimal ? 1 : 0, ',', ' ') . $suffix;
    }

    private function hasFractionalComponent(float $value): bool
    {
        return abs($value - round($value)) > 0.00001;
    }

    private function getDashboardVehiclesByState(
        int $stateId,
        int $supplierId,
        ?Carbon $filterStart,
        ?Carbon $filterEnd,
        string $sortBy = 'latest_added',
    ) {
        $dateField = $stateId === 12 ? 'sale_date' : 'purchase_date';

        $query = Vehicle::query()
            ->with([
                'tasks:id,vehicle_id,cost,is_cost',
                'model:id,name,brand_id',
                'model.brand:id,name,logo',
            ])
            ->where('supplier_id', $supplierId)
            ->where('state_id', $stateId);

        $filteredQuery = $this->applyDateFilterToQuery($query, $dateField, $filterStart, $filterEnd);

        $sortedQuery = $this->applyVehicleSortToQuery(
            $filteredQuery,
            $sortBy,
            $stateId,
        );

        return (clone $sortedQuery)
            ->limit(6)
            ->get()
            ->map(function ($vehicle) use ($stateId) {
                $purchaseDate = $vehicle->purchase_date ? Carbon::parse($vehicle->purchase_date) : null;
                $saleDate = $vehicle->sale_date ? Carbon::parse($vehicle->sale_date) : null;
                $comparisonDate = $stateId === 12
                    ? ($saleDate ?: Carbon::today())
                    : Carbon::today();
                $weeksInStock = $purchaseDate
                    ? max(0, (int) ceil($purchaseDate->diffInDays($comparisonDate) / 7))
                    : 0;
                $totalCosts = round((float) $vehicle->tasks
                    ->where('is_cost', 1)
                    ->sum('cost'), 2);
                $purchasePrice = round((float) ($vehicle->purchase_price ?? 0), 2);
                $salePrice = round((float) ($vehicle->sale_price ?? 0), 2);
                $profitAmount = $stateId === 12
                    ? round((float) ($salePrice - $purchasePrice - $totalCosts), 2)
                    : 0.0;
                $profitMargin = $stateId === 12 && $salePrice > 0
                    ? round((($profitAmount / $salePrice) * 100), 2)
                    : 0.0;

                return [
                    'id' => $vehicle->id,
                    'created_at' => $vehicle->created_at,
                    'reg_num' => $vehicle->reg_num,
                    'year' => $vehicle->year,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'purchase_date' => $vehicle->purchase_date,
                    'sale_date' => $vehicle->sale_date,
                    'weeks_in_stock' => $weeksInStock,
                    'total_costs' => $totalCosts,
                    'profit_amount' => $profitAmount,
                    'profit_margin' => $profitMargin,
                    'title' => $vehicle->model?->name,
                    'model' => $vehicle->model,
                    'tasks' => $vehicle->tasks,
                ];
            })
            ->values();
    }

    private function applyVehicleSortToQuery($query, string $sortBy, int $stateId)
    {
        $scopedQuery = clone $query;

        return match ($sortBy) {
            'oldest_in_stock' => $scopedQuery
                ->orderBy('created_at')
                ->orderBy('id'),
            'highest_price' => $scopedQuery
                ->orderByDesc($stateId === 12 ? 'sale_price' : 'purchase_price')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            default => $scopedQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };
    }

    private function getCurrentSupplierId(): int
    {
        $user = Auth::user();
        $role = $user->getRoleNames()[0] ?? null;

        return match ($role) {
            'Supplier' => $user->supplier->id,
            'User' => $user->supplier->boss_id,
            default => $user->supplier->id,
        };
    }

}
