@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - Liyaa Garage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }

        .header {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .header:after {
            content: '';
            display: table;
            clear: both;
        }

        .invoice-title {
            float: left;
            width: 50%;
        }

        .company-info {
            float: right;
            width: 50%;
            text-align: right;
        }

        .info-container {
            width: 100%;
            margin: 15px 0;
            background: #f8f9fa;
        }

        .info-container:after {
            content: '';
            display: table;
            clear: both;
        }

        .info-section {
            float: left;
            width: 31%;
            padding: 10px;
            border-right: 1px solid #eee;
        }

        .info-section:last-child {
            border-right: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .total-section {
            text-align: right;
            margin: 15px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 10px;
        }

        h1 {
            font-size: 24px;
            margin: 0;
        }

        h2 {
            font-size: 16px;
            margin: 0;
        }

        h3 {
            font-size: 12px;
            margin: 0 0 5px 0;
        }

        p {
            margin: 3px 0;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@foreach($sales as $sale)
    <div class="invoice-container">
        <div class="header">
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p>#{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</p>
            </div>
            <div class="company-info">
                <h2>Liyaa Garage</h2>
                <p>R. Hulhudhuffaaru</p>
                <p>Liyaage</p>
                <p>7626626</p>
            </div>
        </div>

        <div class="info-container">
            <div class="info-section">
                <h3>BILL TO</h3>
                <p><strong>Customer Name</strong><br>{{ $sale->customer->name }}</p>
                <p><strong>Vehicle</strong><br>{{ $sale->vehicle->number }}</p>
                <p><strong>Phone</strong><br>{{ $sale->customer->phone }}</p>
            </div>

            <div class="info-section">
                <h3>INVOICE DETAILS</h3>
                <p><strong>Invoice Number</strong><br>{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</p>
                <p><strong>Date Issued</strong><br>{{ Carbon::parse($sale->date)->format('M d, Y') }}</p>
                <p><strong>Payment Status</strong><br>{{ ucfirst($sale->transaction_type->value) }}</p>
            </div>

            <div class="info-section">
                <h3>PAYMENT INFORMATION</h3>
                <p><strong>Bank</strong><br>BML</p>
                <p><strong>Account Name</strong><br>Liyaa Garage</p>
                <p><strong>Account Number</strong><br>7626626</p>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th>ITEM DESCRIPTION</th>
                <th>QUANTITY</th>
                <th>UNIT PRICE</th>
                <th>TOTAL AMOUNT</th>
            </tr>
            </thead>
            <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->stockItem->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>MVR {{ number_format($item->unit_price, 2) }}</td>
                    <td>MVR {{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="total-section">
            @if($sale->discount_amount > 0)
                <p>Subtotal: MVR {{ number_format($sale->subtotal_amount, 2) }}</p>
                <p>Discount ({{ $sale->discount_percentage }}%): MVR {{ number_format($sale->discount_amount, 2) }}</p>
            @endif
            <h2>Total: MVR {{ number_format($sale->total_amount, 2) }}</h2>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact us at +960 333 4456 or email at
                support@liyaagarage.mv</p>
            <p>© {{ date('Y') }} Liyaa Garage. All rights reserved.</p>
        </div>
    </div>
    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
