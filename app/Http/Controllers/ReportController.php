<?php

namespace App\Http\Controllers;

use Log;
use Exception;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Pos;
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
                'transaction_types' => 'nullable|array',
                'transaction_types.*' => 'string',
            ]);

            Log::info('Generating new report', [
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'transaction_types' => $validated['transaction_types'] ?? 'all',
            ]);

            // Generate report name in the format POSReport(Date)
            $fromDate = Carbon::parse($validated['from_date'])->format('Y-m-d');
            $reportName = "POSReport({$fromDate})";

            // Get POS data for the specified date range
            $query = Pos::with(['customer', 'vehicle'])
                ->whereBetween('date', [$validated['from_date'], $validated['to_date']]);

            // Filter by transaction types if provided
            if (!empty($validated['transaction_types'])) {
                $query->whereIn('transaction_type', $validated['transaction_types']);
            }

            $posRecords = $query->get();

            // Log the query for debugging
            Log::info('POS query for report generation', [
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'transaction_types' => $validated['transaction_types'] ?? 'all',
                'pos_records_count' => $posRecords->count(),
            ]);

            // Calculate totals
            $totalAmount = $posRecords->sum('total_amount');

            // Calculate totals by transaction type
            $totalCash = $posRecords->where('transaction_type', TransactionType::CASH)->sum('total_amount');
            $totalTransfer = $posRecords->where('transaction_type', TransactionType::TRANSFER)->sum('total_amount');
            $totalPending = $posRecords->where('transaction_type', TransactionType::PENDING)->sum('total_amount');

            Log::info('Generating PDF with data', [
                'pos_records_count' => $posRecords->count(),
                'totalAmount' => $totalAmount,
                'totalCash' => $totalCash,
                'totalTransfer' => $totalTransfer,
                'totalPending' => $totalPending,
            ]);

            try {
                // Generate PDF with explicit options
                $pdf = PDF::loadView('pdf.report', [
                    'sales' => $posRecords, // Keep 'sales' as the view variable name
                    'totalAmount' => $totalAmount,
                    'totalCash' => $totalCash,
                    'totalTransfer' => $totalTransfer,
                    'totalPending' => $totalPending,
                    'fromDate' => $validated['from_date'],
                    'toDate' => $validated['to_date'],
                    'reportName' => $reportName,
                    'transactionTypes' => $validated['transaction_types'] ?? [],
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
                    'transaction_types' => $validated['transaction_types'] ?? null,
                ]);

                Log::info('Report record created', [
                    'report_id' => $report->id,
                    'file_path' => $filePath,
                    'transaction_types' => $report->transaction_types,
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

                    $query = Pos::with(['customer', 'vehicle'])
                        ->whereBetween('date', [$report->from_date, $report->to_date]);

                    // Filter by transaction types if provided
                    if (!empty($report->transaction_types)) {
                        $query->whereIn('transaction_type', $report->transaction_types);
                    }

                    $posRecords = $query->get();

                    // Log the query for debugging
                    Log::info('POS query for batch download', [
                        'query' => $query->toSql(),
                        'bindings' => $query->getBindings(),
                        'transaction_types' => $report->transaction_types ?? 'all',
                        'pos_records_count' => $posRecords->count(),
                    ]);

                    // Calculate totals
                    $totalAmount = $posRecords->sum('total_amount');

                    // Calculate totals by transaction type
                    $totalCash = $posRecords->where('transaction_type', TransactionType::CASH)->sum('total_amount');
                    $totalTransfer = $posRecords->where('transaction_type', TransactionType::TRANSFER)->sum('total_amount');
                    $totalPending = $posRecords->where('transaction_type', TransactionType::PENDING)->sum('total_amount');

                    Log::info('Generating PDF with data for batch download', [
                        'report_id' => $report->id,
                        'pos_records_count' => $posRecords->count(),
                        'totalAmount' => $totalAmount,
                        'totalCash' => $totalCash,
                        'totalTransfer' => $totalTransfer,
                        'totalPending' => $totalPending,
                    ]);

                    try {
                        // Generate PDF with explicit options
                        $pdf = PDF::loadView('pdf.report', [
                            'sales' => $posRecords, // Keep 'sales' as the view variable name
                            'totalAmount' => $totalAmount,
                            'totalCash' => $totalCash,
                            'totalTransfer' => $totalTransfer,
                            'totalPending' => $totalPending,
                            'fromDate' => $report->from_date,
                            'toDate' => $report->to_date,
                            'reportName' => $report->name,
                            'transactionTypes' => $report->transaction_types ?? [],
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

                $query = Pos::with(['customer', 'vehicle'])
                    ->whereBetween('date', [$report->from_date, $report->to_date]);

                // Filter by transaction types if provided
                if (!empty($report->transaction_types)) {
                    $query->whereIn('transaction_type', $report->transaction_types);
                }

                $posRecords = $query->get();

                // Log the query and POS data for debugging
                Log::info('POS query for download', [
                    'query' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                    'transaction_types' => $report->transaction_types ?? 'all',
                    'pos_records_count' => $posRecords->count(),
                ]);

                // Calculate totals
                $totalAmount = $posRecords->sum('total_amount');

                // Calculate totals by transaction type
                $totalCash = $posRecords->where('transaction_type', TransactionType::CASH)->sum('total_amount');
                $totalTransfer = $posRecords->where('transaction_type', TransactionType::TRANSFER)->sum('total_amount');
                $totalPending = $posRecords->where('transaction_type', TransactionType::PENDING)->sum('total_amount');

                Log::info('Generating PDF with data', [
                    'pos_records_count' => $posRecords->count(),
                    'totalAmount' => $totalAmount,
                    'totalCash' => $totalCash,
                    'totalTransfer' => $totalTransfer,
                    'totalPending' => $totalPending,
                ]);

                try {
                    // Generate PDF with explicit options
                    $pdf = PDF::loadView('pdf.report', [
                        'sales' => $posRecords, // Keep 'sales' as the view variable name
                        'totalAmount' => $totalAmount,
                        'totalCash' => $totalCash,
                        'totalTransfer' => $totalTransfer,
                        'totalPending' => $totalPending,
                        'fromDate' => $report->from_date,
                        'toDate' => $report->to_date,
                        'reportName' => $report->name,
                        'transactionTypes' => $report->transaction_types ?? [],
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
