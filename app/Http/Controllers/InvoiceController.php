<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function downloadPdf(Sale $sale)
    {
        $pdf = PDF::loadView('pdf.invoice', [
            'sale' => $sale,
        ]);

        // Optional: Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$sale->id}.pdf");
    }
}
