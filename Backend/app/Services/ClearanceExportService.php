<?php

// app/Services/ClearanceExportService.php

namespace App\Services;

use App\Models\Clearance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ClearanceExportService
{
    /**
     * Export clearances to CSV
     */
    public function exportToCsv($startDate = null, $endDate = null, $status = null, $clearanceType = null)
    {
        $query = Clearance::with(['user', 'approver', 'organizations']);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($clearanceType) {
            $query->byType($clearanceType);
        }

        $clearances = $query->orderBy('created_at', 'desc')->get();

        // Create CSV content
        $csv = "ID,User,Clearance Type,Status,Submitted Date,Approved Date,Time to Clear (Days),Approved By,Organizations Status\n";

        foreach ($clearances as $clearance) {
            $timeToClear = $clearance->time_to_clear ?? 'N/A';
            $userName = $clearance->user->name ?? 'N/A';
            $approverName = $clearance->approver->name ?? 'N/A';
            $submittedAt = $clearance->submitted_at ? $clearance->submitted_at->format('Y-m-d H:i') : 'N/A';
            $approvedAt = $clearance->approved_at ? $clearance->approved_at->format('Y-m-d H:i') : 'N/A';
            
            $organizationsStatus = $clearance->organizations
                ->map(fn($org) => "{$org->organization_name}: {$org->status}")
                ->implode('; ');

            $csv .= implode(',', [
                $clearance->id,
                '"' . $userName . '"',
                '"' . $clearance->clearance_type . '"',
                $clearance->status,
                $submittedAt,
                $approvedAt,
                $timeToClear,
                '"' . $approverName . '"',
                '"' . $organizationsStatus . '"',
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export clearances to PDF
     */
    public function exportToPdf($startDate = null, $endDate = null, $status = null, $clearanceType = null)
    {
        $query = Clearance::with(['user', 'approver', 'organizations']);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($clearanceType) {
            $query->byType($clearanceType);
        }

        $clearances = $query->orderBy('created_at', 'desc')->get();

        // Calculate statistics
        $totalClearances = $clearances->count();
        $approvedCount = $clearances->where('status', 'approved')->count();
        $pendingCount = $clearances->where('status', 'pending')->count();
        $rejectedCount = $clearances->where('status', 'rejected')->count();
        $avgTimeToClear = $clearances->where('status', 'approved')
            ->filter(fn($c) => $c->time_to_clear !== null)
            ->avg('time_to_clear');

        $data = [
            'clearances' => $clearances,
            'startDate' => $startDate ? Carbon::parse($startDate)->format('F d, Y') : 'N/A',
            'endDate' => $endDate ? Carbon::parse($endDate)->format('F d, Y') : 'N/A',
            'status' => $status,
            'clearanceType' => $clearanceType,
            'totalClearances' => $totalClearances,
            'approvedCount' => $approvedCount,
            'pendingCount' => $pendingCount,
            'rejectedCount' => $rejectedCount,
            'avgTimeToClear' => round($avgTimeToClear ?? 0, 2),
            'generatedAt' => now()->format('F d, Y h:i A'),
        ];

        $html = $this->generatePdfHtml($data);

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10);
    }

    /**
     * Generate PDF HTML content
     */
    protected function generatePdfHtml($data)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Clearance Report</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { margin: 0; font-size: 18px; }
                .header p { margin: 5px 0; color: #666; }
                .summary { margin-bottom: 20px; }
                .summary-grid { display: table; width: 100%; }
                .summary-item { display: table-cell; padding: 10px; text-align: center; background: #f5f5f5; border: 1px solid #ddd; }
                .summary-item .label { font-weight: bold; display: block; margin-bottom: 5px; }
                .summary-item .value { font-size: 16px; color: #333; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
                th { background-color: #4CAF50; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .status-approved { color: green; font-weight: bold; }
                .status-pending { color: orange; font-weight: bold; }
                .status-rejected { color: red; font-weight: bold; }
                .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Clearance System Report</h1>
                <p>Generated on: ' . $data['generatedAt'] . '</p>
                <p>Period: ' . $data['startDate'] . ' to ' . $data['endDate'] . '</p>
            </div>

            <div class="summary">
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="label">Total Clearances</span>
                        <span class="value">' . $data['totalClearances'] . '</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Approved</span>
                        <span class="value">' . $data['approvedCount'] . '</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Pending</span>
                        <span class="value">' . $data['pendingCount'] . '</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Rejected</span>
                        <span class="value">' . $data['rejectedCount'] . '</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Avg. Time to Clear</span>
                        <span class="value">' . $data['avgTimeToClear'] . ' days</span>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Approved</th>
                        <th>Days</th>
                        <th>Organizations</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data['clearances'] as $clearance) {
            $statusClass = 'status-' . $clearance->status;
            $userName = $clearance->user->name ?? 'N/A';
            $submittedAt = $clearance->submitted_at ? $clearance->submitted_at->format('Y-m-d') : 'N/A';
            $approvedAt = $clearance->approved_at ? $clearance->approved_at->format('Y-m-d') : 'N/A';
            $timeToClear = $clearance->time_to_clear ?? 'N/A';
            $organizations = $clearance->organizations->count();

            $html .= '
                    <tr>
                        <td>' . $clearance->id . '</td>
                        <td>' . htmlspecialchars($userName) . '</td>
                        <td>' . htmlspecialchars($clearance->clearance_type) . '</td>
                        <td class="' . $statusClass . '">' . strtoupper($clearance->status) . '</td>
                        <td>' . $submittedAt . '</td>
                        <td>' . $approvedAt . '</td>
                        <td>' . $timeToClear . '</td>
                        <td>' . $organizations . ' org(s)</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer">
                <p>This report is generated automatically by the Clearance System.</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}