<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

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

            return response()->json([
                'success' => true,
                'data' => [
                    'priceByMonth' => $vehiclePriceSummary['months'],
                    'totalPurchasePrice' => $vehiclePriceSummary['totalPurchasePrice'],
                    'totalSalePrice' => $vehiclePriceSummary['totalSalePrice'],
                    'totalCost' => $vehiclePriceSummary['totalCost'],
                    'totalProfit' => $vehiclePriceSummary['totalProfit'],
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
            $stockVehiclesQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->where('state_id', 10);
            $purchasedVehiclesQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->whereNotNull('purchase_price')
                ->whereNotNull('purchase_date');
            $soldVehiclesQuery = Vehicle::query()
                ->where('supplier_id', $supplierId)
                ->where('state_id', 12)
                ->whereNotNull('sale_price')
                ->whereNotNull('sale_date');

            $vehiclesInStock = (clone $stockVehiclesQuery)->count();
            $stockVehiclesPurchasePrice = round((float) (clone $stockVehiclesQuery)->sum('purchase_price'), 2);
            $stockVehiclesMonthlyVariation = $this->getMonthlyVariationForQuery(
                $stockVehiclesQuery,
                'purchase_date',
            );

            $purchasedVehiclesCount = (clone $purchasedVehiclesQuery)->count();
            $purchasedVehiclesPrice = round((float) (clone $purchasedVehiclesQuery)->sum('purchase_price'), 2);
            $purchasedVehiclesMonthlyVariation = $this->getMonthlyVariationForQuery(
                $purchasedVehiclesQuery,
                'purchase_date',
            );

            $soldVehiclesCount = (clone $soldVehiclesQuery)->count();
            $soldVehiclesPrice = round((float) (clone $soldVehiclesQuery)->sum('sale_price'), 2);
            $soldVehiclesMonthlyVariation = $this->getMonthlyVariationForQuery(
                $soldVehiclesQuery,
                'sale_date',
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'vehiclesInStock' => $vehiclesInStock,
                    'stockVehiclesPurchasePrice' => $stockVehiclesPurchasePrice,
                    'stockVehiclesMonthlyVariation' => $stockVehiclesMonthlyVariation,
                    'purchasedVehiclesCount' => $purchasedVehiclesCount,
                    'purchasedVehiclesPrice' => $purchasedVehiclesPrice,
                    'purchasedVehiclesMonthlyVariation' => $purchasedVehiclesMonthlyVariation,
                    'soldVehiclesCount' => $soldVehiclesCount,
                    'soldVehiclesPrice' => $soldVehiclesPrice,
                    'soldVehiclesMonthlyVariation' => $soldVehiclesMonthlyVariation,
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

            $months[] = [
                'month' => $monthKey,
                'month_label' => $cursor->format('M y'),
                'total_purchase_price' => $totalPurchasePrice,
                'total_sale_price' => $totalSalePrice,
                'total_cost' => $totalCost,
                'total_profit' => round((float) ($totalSalePrice - $totalPurchasePrice - $totalCost), 2),
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

    private function getMonthlyVariationForQuery($query, string $dateField): float
    {
        $currentMonthStart = Carbon::today()->startOfMonth();
        $currentMonthEnd = Carbon::today()->endOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $currentMonthStart->copy()->subMonth()->endOfMonth();

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

    private function calculatePercentageChange(int $previousValue, int $currentValue): float
    {
        if ($previousValue === 0) {
            return $currentValue > 0 ? 100.0 : 0.0;
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, 2);
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
