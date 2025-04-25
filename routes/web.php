<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return redirect('/admin/login');
});


Route::get('/sales/{sale}/invoice/pdf', [InvoiceController::class, 'downloadPdf'])
    ->name('sales.invoice.pdf')
    ->middleware(['auth']);

Route::get('/quotations/{quotation}/quotation/pdf', [InvoiceController::class, 'downloadPdf'])
    ->name('quotations.pdf')
    ->middleware(['auth']);
