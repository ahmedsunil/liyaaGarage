<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    public function downloadPdf(Quotation $quotation)
    {
        $pdf = PDF::loadView('pdf.quotation', [
            'quotation' => $quotation,
        ]);

        // Optional: Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("quotation-{$quotation->id}.pdf");
    }
}
