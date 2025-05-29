<?php

namespace App\Http\Controllers;

use App\Models\Pos;
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

    public function downloadPosPdf(Pos $pos)
    {
        // Convert the JSON sale_items to a collection similar to the Sale->items relationship
        $saleItemsCollection = collect($pos->sale_items)->map(function ($item) {
            // Create an object with properties similar to SaleItem
            $itemObject = new \stdClass();

            // Explicitly set all required properties
            $itemObject->stock_item_id = $item['stock_item_id'] ?? null;
            $itemObject->quantity = $item['quantity'] ?? 0;
            $itemObject->unit_price = $item['unit_price'] ?? 0;
            $itemObject->total_price = $item['total_price'] ?? 0;

            // Add a stockItem property that mimics the SaleItem->stockItem relationship
            $itemObject->stockItem = \App\Models\StockItem::find($item['stock_item_id']);

            return $itemObject;
        });

        // Create a modified pos object with an 'items' property for the view
        $posWithItems = clone $pos;
        $posWithItems->items = $saleItemsCollection;

        $pdf = PDF::loadView('pdf.single-invoice', ['sale' => $posWithItems]);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->stream();
            },
            "pos-invoice-{$pos->id}.pdf",
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="pos-invoice-'.$pos->id.'.pdf"',
            ]
        );
    }
}
