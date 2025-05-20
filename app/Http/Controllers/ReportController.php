<?php

namespace App\Http\Controllers;

use Log;
use Exception;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Report;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Enums\TransactionType;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);

            Log::info('Generating new report', [
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
            ]);

            // Generate report name in the format SalesReport(Date)
            $fromDate = Carbon::parse($validated['from_date'])->format('Y-m-d');
            $reportName = "SalesReport({$fromDate})";

            // Get sales data for the specified date range
            $sales = Sale::with(['items.stockItem', 'customer', 'vehicle'])
                ->whereBetween('date', [$validated['from_date'], $validated['to_date']])
                ->get();

            // Log the query for debugging
            Log::info('Sales query for report generation', [
                'query' => Sale::with(['items.stockItem', 'customer', 'vehicle'])
                    ->whereBetween('date', [$validated['from_date'], $validated['to_date']])
                    ->toSql(),
                'bindings' => [$validated['from_date'], $validated['to_date']],
                'sales_count' => $sales->count(),
            ]);

            // Calculate totals
            $totalAmount = $sales->sum('total_amount');

            // Calculate totals by transaction type
            $totalCash = $sales->where('transaction_type', TransactionType::CASH)->sum('total_amount');
            $totalTransfer = $sales->where('transaction_type', TransactionType::TRANSFER)->sum('total_amount');
            $totalPending = $sales->where('transaction_type', TransactionType::PENDING)->sum('total_amount');

            Log::info('Generating PDF with data', [
                'sales_count' => $sales->count(),
                'totalAmount' => $totalAmount,
                'totalCash' => $totalCash,
                'totalTransfer' => $totalTransfer,
                'totalPending' => $totalPending,
            ]);

            try {
                // Generate PDF with explicit options
                $pdf = PDF::loadView('pdf.report', [
                    'sales' => $sales,
                    'totalAmount' => $totalAmount,
                    'totalCash' => $totalCash,
                    'totalTransfer' => $totalTransfer,
                    'totalPending' => $totalPending,
                    'fromDate' => $validated['from_date'],
                    'toDate' => $validated['to_date'],
                    'reportName' => $reportName,
                ]);

                // Set paper size and orientation
                $pdf->setPaper('a4', 'portrait');

                // Ensure the reports directory exists
                Storage::disk('public')->makeDirectory('reports');

                // Save PDF to storage
                $fileName = 'report_'.time().'.pdf';
                $filePath = 'reports/'.$fileName;
                Storage::disk('public')->put($filePath, $pdf->output());

                Log::info('PDF generated successfully', [
                    'file_path' => $filePath,
                ]);

                // Create a report record
                $report = Report::create([
                    'name' => $reportName,
                    'from_date' => $validated['from_date'],
                    'to_date' => $validated['to_date'],
                    'file_path' => $filePath,
                ]);

                Log::info('Report record created', [
                    'report_id' => $report->id,
                    'file_path' => $filePath,
                ]);

                return redirect()->route('filament.admin.resources.reports.index')
                    ->with('success', 'Report generated successfully.');
            } catch (Exception $e) {
                Log::error('Error generating PDF in inner try block', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Re-throw to be caught by outer catch block
            }
        } catch (Exception $e) {
            Log::error('Error generating new PDF report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error generating report: '.$e->getMessage());
        }
    }

    public function batchDownload(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            $reports = Report::whereIn('id', $ids)->get();

            if ($reports->isEmpty()) {
                Log::warning('Batch download attempted with no reports selected');
                return back()->with('error', 'No reports selected.');
            }

            if ($reports->count() === 1) {
                $report = $reports->first();
                Log::info('Single report download redirected to download method', [
                    'report_id' => $report->id,
                ]);
                return $this->download($report);
            }

            // Log batch download request
            Log::info('Batch downloading reports', [
                'report_count' => $reports->count(),
                'report_ids' => $ids,
            ]);

            // Create a zip file for multiple reports
            $zipFileName = 'reports_'.time().'.zip';
            $zipFilePath = storage_path('app/public/temp/'.$zipFileName);

            // Ensure the directory exists
            if (! file_exists(dirname($zipFilePath))) {
                Log::info('Creating temp directory for zip file', [
                    'directory' => dirname($zipFilePath),
                ]);
                mkdir(dirname($zipFilePath), 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
                Log::error('Could not create zip file', [
                    'zip_file_path' => $zipFilePath,
                ]);
                return back()->with('error', 'Could not create zip file.');
            }

            foreach ($reports as $report) {
                // If the file doesn't exist, regenerate it
                if (! $report->file_path || ! Storage::disk('public')->exists($report->file_path)) {
                    Log::info('Report file not found, regenerating for batch download', [
                        'report_id' => $report->id,
                        'file_path' => $report->file_path,
                        'from_date' => $report->from_date,
                        'to_date' => $report->to_date,
                    ]);

                    $sales = Sale::with(['items.stockItem', 'customer', 'vehicle'])
                        ->whereBetween('date', [$report->from_date, $report->to_date])
                        ->get();

                    // Log the query for debugging
                    Log::info('Sales query for batch download', [
                        'query' => Sale::with(['items.stockItem', 'customer', 'vehicle'])
                            ->whereBetween('date', [$report->from_date, $report->to_date])
                            ->toSql(),
                        'bindings' => [$report->from_date, $report->to_date],
                        'sales_count' => $sales->count(),
                    ]);

                    // Calculate totals
                    $totalAmount = $sales->sum('total_amount');

                    // Calculate totals by transaction type
                    $totalCash = $sales->where('transaction_type', TransactionType::CASH)->sum('total_amount');
                    $totalTransfer = $sales->where('transaction_type', TransactionType::TRANSFER)->sum('total_amount');
                    $totalPending = $sales->where('transaction_type', TransactionType::PENDING)->sum('total_amount');

                    Log::info('Generating PDF with data for batch download', [
                        'report_id' => $report->id,
                        'sales_count' => $sales->count(),
                        'totalAmount' => $totalAmount,
                        'totalCash' => $totalCash,
                        'totalTransfer' => $totalTransfer,
                        'totalPending' => $totalPending,
                    ]);

                    try {
                        // Generate PDF with explicit options
                        $pdf = PDF::loadView('pdf.report', [
                            'sales' => $sales,
                            'totalAmount' => $totalAmount,
                            'totalCash' => $totalCash,
                            'totalTransfer' => $totalTransfer,
                            'totalPending' => $totalPending,
                            'fromDate' => $report->from_date,
                            'toDate' => $report->to_date,
                            'reportName' => $report->name,
                        ]);

                        // Set paper size and orientation
                        $pdf->setPaper('a4', 'portrait');

                        // Ensure the reports directory exists
                        Storage::disk('public')->makeDirectory('reports');

                        // Save PDF to storage
                        $fileName = 'report_'.time().'.pdf';
                        $filePath = 'reports/'.$fileName;
                        Storage::disk('public')->put($filePath, $pdf->output());

                        Log::info('PDF generated successfully for batch download', [
                            'report_id' => $report->id,
                            'file_path' => $filePath,
                        ]);

                        // Update report record
                        $report->update([
                            'file_path' => $filePath,
                        ]);

                        // Add the newly generated file to the zip
                        $zip->addFile(
                            Storage::disk('public')->path($filePath),
                            $report->name.'.pdf'
                        );

                        Log::info('Added newly generated file to zip', [
                            'report_id' => $report->id,
                            'file_path' => $filePath,
                        ]);
                    } catch (Exception $e) {
                        Log::error('Error generating PDF in batch download inner try block', [
                            'report_id' => $report->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        throw $e; // Re-throw to be caught by outer catch block
                    }
                } else {
                    // Add the existing file to the zip
                    Log::info('Adding existing file to zip', [
                        'report_id' => $report->id,
                        'file_path' => $report->file_path,
                    ]);

                    $zip->addFile(
                        Storage::disk('public')->path($report->file_path),
                        $report->name.'.pdf'
                    );
                }
            }

            $zip->close();

            Log::info('Zip file created successfully', [
                'zip_file_path' => $zipFilePath,
                'report_count' => $reports->count(),
            ]);

            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Log::error('Error batch downloading reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error downloading reports: '.$e->getMessage());
        }
    }

    public function download(Report $report)
    {
        try {
            if (! $report->file_path || ! Storage::disk('public')->exists($report->file_path)) {
                // If the file doesn't exist, regenerate it
                Log::info('Report file not found, regenerating', [
                    'report_id' => $report->id,
                    'file_path' => $report->file_path,
                    'from_date' => $report->from_date,
                    'to_date' => $report->to_date,
                ]);

                $sales = Sale::with(['items.stockItem', 'customer', 'vehicle'])
                    ->whereBetween('date', [$report->from_date, $report->to_date])
                    ->get();

                // Log the query and sales data for debugging
                Log::info('Sales query for download', [
                    'query' => Sale::with(['items.stockItem', 'customer', 'vehicle'])
                        ->whereBetween('date', [$report->from_date, $report->to_date])
                        ->toSql(),
                    'bindings' => [$report->from_date, $report->to_date],
                    'sales_count' => $sales->count(),
                ]);

                // Calculate totals
                $totalAmount = $sales->sum('total_amount');

                // Calculate totals by transaction type
                $totalCash = $sales->where('transaction_type', TransactionType::CASH)->sum('total_amount');
                $totalTransfer = $sales->where('transaction_type', TransactionType::TRANSFER)->sum('total_amount');
                $totalPending = $sales->where('transaction_type', TransactionType::PENDING)->sum('total_amount');

                Log::info('Generating PDF with data', [
                    'sales_count' => $sales->count(),
                    'totalAmount' => $totalAmount,
                    'totalCash' => $totalCash,
                    'totalTransfer' => $totalTransfer,
                    'totalPending' => $totalPending,
                ]);

                try {
                    // Generate PDF with explicit options
                    $pdf = PDF::loadView('pdf.report', [
                        'sales' => $sales,
                        'totalAmount' => $totalAmount,
                        'totalCash' => $totalCash,
                        'totalTransfer' => $totalTransfer,
                        'totalPending' => $totalPending,
                        'fromDate' => $report->from_date,
                        'toDate' => $report->to_date,
                        'reportName' => $report->name,
                    ]);

                    // Set paper size and orientation
                    $pdf->setPaper('a4', 'portrait');

                    // Ensure the reports directory exists
                    Storage::disk('public')->makeDirectory('reports');

                    // Save PDF to storage
                    $fileName = 'report_'.time().'.pdf';
                    $filePath = 'reports/'.$fileName;
                    Storage::disk('public')->put($filePath, $pdf->output());

                    Log::info('PDF generated successfully', [
                        'report_id' => $report->id,
                        'file_path' => $filePath,
                    ]);

                    // Update report record
                    $report->update([
                        'file_path' => $filePath,
                    ]);

                    return response()->download(
                        Storage::disk('public')->path($filePath),
                        $report->name.'.pdf'
                    );
                } catch (Exception $e) {
                    Log::error('Error generating PDF in inner try block', [
                        'report_id' => $report->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e; // Re-throw to be caught by outer catch block
                }
            }

            Log::info('Downloading existing report file', [
                'report_id' => $report->id,
                'file_path' => $report->file_path,
            ]);

            return response()->download(
                Storage::disk('public')->path($report->file_path),
                $report->name.'.pdf'
            );
        } catch (Exception $e) {
            Log::error('Error generating or downloading PDF report', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error generating report: '.$e->getMessage());
        }
    }
}
