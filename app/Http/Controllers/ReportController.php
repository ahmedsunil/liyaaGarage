<?php

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\Sale;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);

            // Generate report name in the format SalesReport(Date)
            $fromDate = \Carbon\Carbon::parse($validated['from_date'])->format('Y-m-d');
            $reportName = "SalesReport({$fromDate})";

            // Get sales data for the specified date range
            $sales = Sale::with(['items.stockItem', 'customer', 'vehicle'])
                ->whereBetween('date', [$validated['from_date'], $validated['to_date']])
                ->get();

            // Log the query for debugging
            \Log::info('Sales query', [
                'query' => Sale::with(['items.stockItem', 'customer', 'vehicle'])
                    ->whereBetween('date', [$validated['from_date'], $validated['to_date']])
                    ->toSql(),
                'bindings' => [$validated['from_date'], $validated['to_date']]
            ]);

            // Log sales data for debugging
            \Log::info('Generating new report PDF', [
                'sales_count' => $sales->count(),
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'sales_data' => $sales->take(5)->map(function($sale) {
                    return [
                        'id' => $sale->id,
                        'date' => $sale->created_at,
                        'customer' => $sale->customer ? $sale->customer->name : 'N/A',
                        'items_count' => $sale->items->count(),
                        'total_amount' => $sale->total_amount
                    ];
                })
            ]);

            // Calculate totals
            $totalAmount = $sales->sum('total_amount');
            $totalItems = $sales->sum(function ($sale) {
                return $sale->items->count();
            });

            // Generate PDF with explicit options
            $pdf = PDF::loadView('pdf.report', [
                'sales' => $sales,
                'totalAmount' => $totalAmount,
                'totalItems' => $totalItems,
                'fromDate' => $validated['from_date'],
                'toDate' => $validated['to_date'],
                'reportName' => $reportName,
            ]);

            // Set paper size and orientation
            $pdf->setPaper('a4', 'portrait');

            // Ensure the reports directory exists
            Storage::disk('public')->makeDirectory('reports');

            // Save PDF to storage
            $fileName = 'report_' . time() . '.pdf';
            $filePath = 'reports/' . $fileName;
            Storage::disk('public')->put($filePath, $pdf->output());

            // Create report record
            $report = Report::create([
                'name' => $reportName,
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'file_path' => $filePath,
            ]);

            return redirect()->route('filament.admin.resources.reports.index')
                ->with('success', 'Report generated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error generating new PDF report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error generating report: ' . $e->getMessage());
        }
    }

    public function download(Report $report)
    {
        try {
            if (!$report->file_path || !Storage::disk('public')->exists($report->file_path)) {
                // If the file doesn't exist, regenerate it
                $sales = Sale::with(['items.stockItem', 'customer', 'vehicle'])
                    ->whereBetween('date', [$report->from_date, $report->to_date])
                    ->get();

                // Log the query for debugging
                \Log::info('Sales query for download', [
                    'query' => Sale::with(['items.stockItem', 'customer', 'vehicle'])
                        ->whereBetween('date', [$report->from_date, $report->to_date])
                        ->toSql(),
                    'bindings' => [$report->from_date, $report->to_date]
                ]);

                // Log sales data for debugging
                \Log::info('Regenerating report PDF', [
                    'report_id' => $report->id,
                    'sales_count' => $sales->count(),
                    'from_date' => $report->from_date,
                    'to_date' => $report->to_date,
                    'sales_data' => $sales->take(5)->map(function($sale) {
                        return [
                            'id' => $sale->id,
                            'date' => $sale->created_at,
                            'customer' => $sale->customer ? $sale->customer->name : 'N/A',
                            'items_count' => $sale->items->count(),
                            'total_amount' => $sale->total_amount
                        ];
                    })
                ]);

                // Calculate totals
                $totalAmount = $sales->sum('total_amount');
                $totalItems = $sales->sum(function ($sale) {
                    return $sale->items->count();
                });

                // Generate PDF with explicit options
                $pdf = PDF::loadView('pdf.report', [
                    'sales' => $sales,
                    'totalAmount' => $totalAmount,
                    'totalItems' => $totalItems,
                    'fromDate' => $report->from_date,
                    'toDate' => $report->to_date,
                    'reportName' => $report->name,
                ]);

                // Set paper size and orientation
                $pdf->setPaper('a4', 'portrait');

                // Ensure the reports directory exists
                Storage::disk('public')->makeDirectory('reports');

                // Save PDF to storage
                $fileName = 'report_' . time() . '.pdf';
                $filePath = 'reports/' . $fileName;
                Storage::disk('public')->put($filePath, $pdf->output());

                // Update report record
                $report->update([
                    'file_path' => $filePath,
                ]);

                return response()->download(
                    Storage::disk('public')->path($filePath),
                    $report->name . '.pdf'
                );
            }

            return response()->download(
                Storage::disk('public')->path($report->file_path),
                $report->name . '.pdf'
            );
        } catch (\Exception $e) {
            \Log::error('Error generating PDF report', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error generating report: ' . $e->getMessage());
        }
    }

    public function batchDownload(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            $reports = Report::whereIn('id', $ids)->get();

            if ($reports->isEmpty()) {
                return back()->with('error', 'No reports selected.');
            }

            if ($reports->count() === 1) {
                $report = $reports->first();
                return $this->download($report);
            }

            // Log batch download request
            \Log::info('Batch downloading reports', [
                'report_count' => $reports->count(),
                'report_ids' => $ids
            ]);

            // Create a zip file for multiple reports
            $zipFileName = 'reports_' . time() . '.zip';
            $zipFilePath = storage_path('app/public/temp/' . $zipFileName);

            // Ensure the directory exists
            if (!file_exists(dirname($zipFilePath))) {
                mkdir(dirname($zipFilePath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
                return back()->with('error', 'Could not create zip file.');
            }

            foreach ($reports as $report) {
                // If the file doesn't exist, regenerate it
                if (!$report->file_path || !Storage::disk('public')->exists($report->file_path)) {
                    $sales = Sale::with(['items.stockItem', 'customer', 'vehicle'])
                        ->whereBetween('date', [$report->from_date, $report->to_date])
                        ->get();

                    // Log the query for debugging
                    \Log::info('Sales query for batch download', [
                        'query' => Sale::with(['items.stockItem', 'customer', 'vehicle'])
                            ->whereBetween('date', [$report->from_date, $report->to_date])
                            ->toSql(),
                        'bindings' => [$report->from_date, $report->to_date]
                    ]);

                    // Log regenerating report
                    \Log::info('Regenerating report for batch download', [
                        'report_id' => $report->id,
                        'sales_count' => $sales->count(),
                        'from_date' => $report->from_date,
                        'to_date' => $report->to_date,
                        'sales_data' => $sales->take(5)->map(function($sale) {
                            return [
                                'id' => $sale->id,
                                'date' => $sale->created_at,
                                'customer' => $sale->customer ? $sale->customer->name : 'N/A',
                                'items_count' => $sale->items->count(),
                                'total_amount' => $sale->total_amount
                            ];
                        })
                    ]);

                    // Calculate totals
                    $totalAmount = $sales->sum('total_amount');
                    $totalItems = $sales->sum(function ($sale) {
                        return $sale->items->count();
                    });

                    // Generate PDF with explicit options
                    $pdf = PDF::loadView('pdf.report', [
                        'sales' => $sales,
                        'totalAmount' => $totalAmount,
                        'totalItems' => $totalItems,
                        'fromDate' => $report->from_date,
                        'toDate' => $report->to_date,
                        'reportName' => $report->name,
                    ]);

                    // Set paper size and orientation
                    $pdf->setPaper('a4', 'portrait');

                    // Ensure the reports directory exists
                    Storage::disk('public')->makeDirectory('reports');

                    // Save PDF to storage
                    $fileName = 'report_' . time() . '.pdf';
                    $filePath = 'reports/' . $fileName;
                    Storage::disk('public')->put($filePath, $pdf->output());

                    // Update report record
                    $report->update([
                        'file_path' => $filePath,
                    ]);

                    // Add the newly generated file to the zip
                    $zip->addFile(
                        Storage::disk('public')->path($filePath),
                        $report->name . '.pdf'
                    );
                } else {
                    // Add the existing file to the zip
                    $zip->addFile(
                        Storage::disk('public')->path($report->file_path),
                        $report->name . '.pdf'
                    );
                }
            }

            $zip->close();

            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error batch downloading reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error downloading reports: ' . $e->getMessage());
        }
    }
}
