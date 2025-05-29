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
            $itemObject = (object) $item;

            // Ensure quantity is explicitly set as a property on the object
            if (isset($item['quantity'])) {
                $itemObject->quantity = $item['quantity'];
            } else {
                // Set a default quantity of 1 if not specified
                $itemObject->quantity = 1;
            }

            // Ensure unit_price is explicitly set as a property on the object
            if (isset($item['unit_price'])) {
                $itemObject->unit_price = $item['unit_price'];
            } else {
                // Set a default unit_price of 0 if not specified
                $itemObject->unit_price = 0;
            }

            // Ensure total_price is explicitly set as a property on the object
            if (isset($item['total_price'])) {
                $itemObject->total_price = $item['total_price'];
            } else {
                // Calculate total_price from quantity and unit_price if not specified
                $itemObject->total_price = $itemObject->quantity * $itemObject->unit_price;
            }

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
