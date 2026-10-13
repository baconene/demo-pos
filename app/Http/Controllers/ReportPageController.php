<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportPageController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(): Response
    {
        $dailyReport = $this->reportService->getDailySalesReport(Carbon::today());
        $productSales = $this->reportService->getProductSalesReport(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        return Inertia::render('ReportsPage', [
            'initialDailyReport' => $dailyReport,
            'initialProductSales' => $productSales->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'total_quantity' => (int) $item->total_quantity,
                'total_sales' => (float) $item->total_sales,
            ])->values()->toArray(),
        ]);
    }
}
