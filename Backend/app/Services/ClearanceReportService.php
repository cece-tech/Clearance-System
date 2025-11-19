<?php

// app/Services/ClearanceReportService.php

namespace App\Services;

use App\Models\Clearance;
use App\Models\ClearanceOrganization;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearanceReportService
{
    /**
     * Get clearance metrics
     */
    public function getMetrics($startDate = null, $endDate = null, $clearanceType = null)
    {
        $query = Clearance::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($clearanceType) {
            $query->byType($clearanceType);
        }

        $total = $query->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();

        $approvalRate = $total > 0 ? round(($approved / $total) * 100, 2) : 0;

        // Average time to clear (in days)
        $avgTimeToClear = Clearance::query()
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($clearanceType, fn($q) => $q->byType($clearanceType))
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(DATEDIFF(approved_at, submitted_at)) as avg_days')
            ->value('avg_days');

        return [
            'total_clearances' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'rejected' => $rejected,
            'approval_rate' => $approvalRate,
            'average_time_to_clear_days' => round($avgTimeToClear ?? 0, 2),
        ];
    }

    /**
     * Get time-to-clear statistics
     */
    public function getTimeToClearStatistics($startDate = null, $endDate = null, $clearanceType = null, $groupBy = 'month')
    {
        $query = Clearance::query()
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->where('status', 'approved');

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($clearanceType) {
            $query->byType($clearanceType);
        }

        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m',
        };

        $statistics = $query->selectRaw("
                DATE_FORMAT(approved_at, '{$dateFormat}') as period,
                COUNT(*) as total_cleared,
                AVG(DATEDIFF(approved_at, submitted_at)) as avg_days,
                MIN(DATEDIFF(approved_at, submitted_at)) as min_days,
                MAX(DATEDIFF(approved_at, submitted_at)) as max_days
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->period,
                    'total_cleared' => $item->total_cleared,
                    'average_days' => round($item->avg_days, 2),
                    'minimum_days' => $item->min_days,
                    'maximum_days' => $item->max_days,
                ];
            });

        return $statistics;
    }

    /**
     * Get detailed statistics
     */
    public function getDetailedStatistics($startDate = null, $endDate = null)
    {
        $query = Clearance::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        // Statistics by clearance type
        $byType = (clone $query)
            ->select('clearance_type')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved')
            ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
            ->selectRaw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected')
            ->groupBy('clearance_type')
            ->get();

        // Statistics by status
        $byStatus = (clone $query)
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Monthly trends
        $monthlyTrends = (clone $query)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();

        return [
            'by_type' => $byType,
            'by_status' => $byStatus,
            'monthly_trends' => $monthlyTrends,
        ];
    }

    /**
     * Get organization performance
     */
    public function getOrganizationPerformance($startDate = null, $endDate = null)
    {
        $query = ClearanceOrganization::query()
            ->with('clearance');

        if ($startDate && $endDate) {
            $query->whereHas('clearance', function ($q) use ($startDate, $endDate) {
                $q->dateRange($startDate, $endDate);
            });
        }

        $performance = $query->select('organization_name')
            ->selectRaw('COUNT(*) as total_processed')
            ->selectRaw('SUM(CASE WHEN status = "cleared" THEN 1 ELSE 0 END) as cleared')
            ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
            ->selectRaw('SUM(CASE WHEN status = "not_cleared" THEN 1 ELSE 0 END) as not_cleared')
            ->selectRaw('AVG(CASE WHEN cleared_at IS NOT NULL THEN DATEDIFF(cleared_at, created_at) END) as avg_processing_days')
            ->groupBy('organization_name')
            ->get()
            ->map(function ($item) {
                $clearanceRate = $item->total_processed > 0 
                    ? round(($item->cleared / $item->total_processed) * 100, 2) 
                    : 0;

                return [
                    'organization' => $item->organization_name,
                    'total_processed' => $item->total_processed,
                    'cleared' => $item->cleared,
                    'pending' => $item->pending,
                    'not_cleared' => $item->not_cleared,
                    'clearance_rate' => $clearanceRate,
                    'avg_processing_days' => round($item->avg_processing_days ?? 0, 2),
                ];
            });

        return $performance;
    }

    /**
     * Get trends data
     */
    public function getTrends($period = 'month', $clearanceType = null)
    {
        $dateFormat = match($period) {
            'week' => '%Y-%u',
            'quarter' => 'CONCAT(YEAR(created_at), "-Q", QUARTER(created_at))',
            'year' => '%Y',
            default => '%Y-%m',
        };

        $isQuarter = $period === 'quarter';

        $query = Clearance::query();

        if ($clearanceType) {
            $query->byType($clearanceType);
        }

        if ($isQuarter) {
            $trends = $query->selectRaw("
                    CONCAT(YEAR(created_at), '-Q', QUARTER(created_at)) as period,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                ")
                ->groupByRaw("YEAR(created_at), QUARTER(created_at)")
                ->orderByRaw("YEAR(created_at), QUARTER(created_at)")
                ->limit(8)
                ->get();
        } else {
            $trends = $query->selectRaw("
                    DATE_FORMAT(created_at, '{$dateFormat}') as period,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->limit(12)
                ->get();
        }

        return $trends;
    }
}