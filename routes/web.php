<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return redirect('/admin/login');
});


Route::get('/sales/{sale}/invoice/pdf', [InvoiceController::class, 'downloadPdf'])
    ->name('sales.invoice.pdf')
    ->middleware(['auth']);
