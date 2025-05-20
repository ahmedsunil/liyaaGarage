<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;

Route::get('/', function () {
    return redirect('/admin/login');
});


Route::get('/sales/{sale}/invoice/pdf', [InvoiceController::class, 'downloadPdf'])
    ->name('sales.invoice.pdf')
    ->middleware(['auth']);

Route::get('/quotations/{quotation}/quotation/pdf', [QuotationController::class, 'downloadPdf'])
    ->name('quotations.pdf')
    ->middleware(['auth']);
