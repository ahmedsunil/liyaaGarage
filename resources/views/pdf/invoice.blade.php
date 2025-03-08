@php use Carbon\Carbon; @endphp
@php use App\Support\Enums\TransactionType; @endphp
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #334155;
            font-size: 12px;
            line-height: 1.4;
            background-color: #fff;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .page-break {
            page-break-after: always;
        }

        /* Header Styles */
        .invoice-header {
            background-color: #1e293b;
            color: #fff;
            padding: 20px;
            margin-bottom: 30px;
        }

        .invoice-header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .invoice-number {
            font-size: 13px;
            margin-top: 5px;
            opacity: 0.9;
        }

        .company-info {
            text-align: right;
        }

        .company-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .company-address {
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.4;
        }

        .company-contact {
            margin-top: 3px;
            font-size: 12px;
            opacity: 0.9;
        }

        /* Details Section Styles */
        .details-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .details-group {
            display: grid;
            gap: 12px;
        }

        .details-group-title {
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .detail-item {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 20px;
            align-items: baseline;
        }

        .detail-label {
            color: #64748b;
            font-size: 13px;
        }

        .detail-value {
            color: #1e293b;
            font-size: 13px;
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-primary {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        th {
            text-align: left;
            padding: 10px;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            font-size: 12px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 13px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .product-name {
            font-weight: 500;
            color: #1e293b;
        }

        .product-meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Summary Styles */
        .summary-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .summary {
            width: 300px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row.total {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 5px;
            border-bottom: none;
        }

        /* Payment Info Styles */
        .payment-info {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .payment-info-title {
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .payment-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .payment-item {
            display: grid;
            gap: 5px;
        }

        .payment-label {
            color: #64748b;
            font-size: 12px;
        }

        .payment-value {
            color: #1e293b;
            font-size: 13px;
            font-weight: 500;
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            padding: 20px 0;
            border-top: 1px solid #e2e8f0;
            margin-top: 30px;
        }

        .page-number {
            text-align: center;
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }

        /* Notes Styles */
        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 4px;
        }

        .notes-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #64748b;
            font-size: 14px;
        }

        /* Print Styles */
        @media print {
            .page-header {
                display: table-header-group;
            }

            .page-footer {
                display: table-footer-group;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            button {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="invoice-header">
        <div class="invoice-header-content">
            <div>
                <h1 class="invoice-title">INVOICE</h1>
                <p class="invoice-number">#{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</p>
            </div>
            <div class="company-info">
                <div class="company-name">Liyaa Garage</div>
                <div class="company-address">
                    Majeedhee Magu, Male' 20-01<br>
                    Republic of Maldives
                </div>
                <div class="company-contact">
                    +960 333 4455 | info@liyaagarage.mv
                </div>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="details-section">
        <div class="details-group">
            <div class="details-group-title">BILL TO</div>
            <div class="detail-item">
                <div class="detail-label">Customer Name</div>
                <div class="detail-value">{{ $sale->customer->name ?? 'N/A' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Vehicle</div>
                <div class="detail-value">{{ $sale->vehicle->vehicle_number ?? 'N/A' }}</div>
            </div>
            @if(isset($sale->customer->phone))
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">{{ $sale->customer->phone }}</div>
                </div>
            @endif
        </div>

        <div class="details-group">
            <div class="details-group-title">INVOICE DETAILS</div>
            <div class="detail-item">
                <div class="detail-label">Invoice Number</div>
                <div class="detail-value">#{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Date Issued</div>
                <div class="detail-value">{{ Carbon::parse($sale->date)->format('M d, Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Payment Status</div>
                <div class="detail-value">
                    @php
                        $transactionType = $sale->transaction_type instanceof TransactionType
                            ? $sale->transaction_type->value
                            : $sale->transaction_type;

                        $badgeClass = match($transactionType) {
                            'pending' => 'badge',
                            'cash' => 'badge badge-success',
                            'card' => 'badge badge-info',
                            'bank_transfer' => 'badge badge-primary',
                            default => 'badge',
                        };
                    @endphp
                    <span class="{{ $badgeClass }}">
                        {{ ucfirst($transactionType) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
        <tr>
            <th style="width: 40%;">ITEM DESCRIPTION</th>
            <th style="width: 20%;" class="text-center">QUANTITY</th>
            <th style="width: 20%;" class="text-right">UNIT PRICE</th>
            <th style="width: 20%;" class="text-right">AMOUNT</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sale->items as $item)
            <tr>
                <td>
                    <div class="product-name">{{ $item->stockItem->product_name ?? 'Unknown Product' }}</div>
                    @if(isset($item->stockItem) && $item->stockItem->is_liquid)
                        <div class="product-meta">Volume: {{ $item->volume }}ml</div>
                    @endif
                </td>
                <td class="text-center">
                    {{ $item->quantity }}
                    @if(isset($item->stockItem) && $item->stockItem->is_liquid)
                        containers
                    @endif
                </td>
                <td class="text-right">MVR {{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">MVR {{ number_format($item->total_price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-container">
        <div class="summary">
            <div class="summary-row">
                <div>Subtotal</div>
                <div>MVR {{ number_format($sale->subtotal_amount, 2) }}</div>
            </div>

            @if($sale->discount_percentage > 0)
                <div class="summary-row">
                    <div>Discount ({{ $sale->discount_percentage }}%)</div>
                    <div>-MVR {{ number_format($sale->discount_amount, 2) }}</div>
                </div>
            @endif

            <div class="summary-row total">
                <div>Total</div>
                <div>MVR {{ number_format($sale->total_amount, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="payment-info">
        <div class="payment-info-title">PAYMENT INFORMATION</div>
        <div class="payment-details">
            <div class="payment-item">
                <div class="payment-label">Bank Name</div>
                <div class="payment-value">Bank of Maldives</div>
            </div>
            <div class="payment-item">
                <div class="payment-label">Account Name</div>
                <div class="payment-value">Liyaa Garage Pvt Ltd</div>
            </div>
            <div class="payment-item">
                <div class="payment-label">Account Number</div>
                <div class="payment-value">7701-123456-001</div>
            </div>
            <div class="payment-item">
                <div class="payment-label">Reference</div>
                <div class="payment-value">INV-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>
    </div>

    <!-- Notes -->
    @if(!empty($sale->remarks))
        <div class="notes">
            <div class="notes-title">NOTES</div>
            <div>{{ $sale->remarks }}</div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>If you have any questions about this invoice, please contact us at +960 333 4455 or email at
            support@liyaagarage.mv</p>
        <p>© {{ date('Y') }} Liyaa Garage. All rights reserved.</p>
    </div>
</div>

<script type="text/php">
    if (isset($pdf)) {
        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
        $size = 10;
        $font = $fontMetrics->getFont("Helvetica");
        $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
        $x = ($pdf->get_width() - $width) / 2;
        $y = $pdf->get_height() - 35;
        $pdf->page_text($x, $y, $text, $font, $size);
    }
</script>
</body>
</html>
