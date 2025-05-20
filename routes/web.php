<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return redirect('/admin/login');
});


Route::get('/sales/{sale}/invoice/pdf', [InvoiceController::class, 'downloadPdf'])
    ->name('sales.invoice.pdf')
    ->middleware(['auth']);

Route::get('/quotations/{quotation}/quotation/pdf', [QuotationController::class, 'downloadPdf'])
    ->name('quotations.pdf')
    ->middleware(['auth']);

Route::post('/reports/generate', [ReportController::class, 'generate'])
    ->name('reports.generate')
    ->middleware(['auth']);

Route::get('/reports/{report}/download', [ReportController::class, 'download'])
    ->name('reports.download')
    ->middleware(['auth']);

Route::get('/reports/batch-download', [ReportController::class, 'batchDownload'])
    ->name('reports.batch-download')
    ->middleware(['auth']);
