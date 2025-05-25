<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    //    public function downloadPdf(Sale $sale)
    //    {
    //        // Generate PDF
    //        $pdf = PDF::loadView('pdf.single-invoice', [
    //            'sale' => $sale, // Changed from 'sales' to 'sale' for consistency
    //        ]);
    //
    //        return $pdf->download("invoice-{$sale->id}.pdf");
    //    }

    public function downloadPdf(Sale $sale)
    {
        $pdf = PDF::loadView('pdf.single-invoice', ['sale' => $sale]);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->stream();
            },
            "invoice-{$sale->id}.pdf",
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-'.$sale->id.'.pdf"',
            ]
        );
    }
}
