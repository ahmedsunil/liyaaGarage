@php
    use Carbon\Carbon;
    use App\Models\Business;
    use App\Support\Enums\TransactionType;

    $business = Business::first();

    function getLogoData($business) {
    // Check custom logo
    try {
        if ($business->logo_path && Storage::disk('public')->exists($business->logo_path)) {
            return base64_encode(Storage::disk('public')->get($business->logo_path));
        }
    } catch (Exception $e) {
        logger()->error("Logo error: ".$e->getMessage());
    }

    // Check default logo
    try {
        $defaultPath = public_path('images/default-logo.png');
        if (file_exists($defaultPath)) {
            return base64_encode(file_get_contents($defaultPath));
        }
    } catch (Exception $e) {
        logger()->error("Default logo error: ".$e->getMessage());
    }

    return null;
}

// Usage
$logoData = getLogoData($business);


@endphp
    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Invoice - {{ $business->name }}</title>
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

        .rotated-stamp {
            transform: rotate(15deg);
            -webkit-transform: rotate(15deg);
        }
    </style>
</head>
<body>

<div class="invoice-container">
    <div class="header">
        <div class="invoice-title">
            <h1>INVOICE</h1>
            <p>#{{ $business->invoice_number_prefix }}/{{ date('Y') }}
                /{{ str_pad($sale->id + 1000, 4, '0', STR_PAD_LEFT) }}</p></div>
        <div class="company-info">
            @if($logoData)
                <img src="data:image/jpeg;base64,{{ $logoData }}" width="60" alt="Logo">
            @else
                <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                     width="60" alt="Logo">
            @endif
            <h2>{{ $business->name }}</h2>
            <p>{{ $business->street_address }}</p>
            <p>{{ $business->contact }}</p>
            <p>{{ $business->email }}
        </div>
    </div>

    <div class="info-container">
        <div class="info-section">
            <h3>BILL TO</h3>
            <p><strong>Customer Name</strong><br>{{ $sale->customer->name }}</p>
            <p><strong>Vehicle</strong><br>{{ $sale->vehicle?->vehicle_number ?? '0' }}</p>
            <p><strong>Phone</strong><br>{{ $sale->customer->phone }}</p>
        </div>

        <div class="info-section">
            <h3>INVOICE DETAILS</h3>
            <p><strong>Invoice Number</strong><br>#{{ $business->invoice_number_prefix }}/{{ date('Y') }}
                /{{ str_pad($sale->id + 1000, 4, '0', STR_PAD_LEFT) }}</p>
            <p><strong>Date Issued</strong><br>{{ Carbon::parse($sale->date)->format('M d, Y') }}</p>
            <p><strong>Payment Status</strong><br>{{ ucfirst($sale->transaction_type->value) }}</p>
        </div>

        <div class="info-section">
            <h3>PAYMENT INFORMATION</h3>
            <p><strong>Bank</strong><br>{{ Str::upper($business->account_type) }}</p>
            <p><strong>Account Name</strong><br>{{ $business->account_name }}</p>
            <p><strong>Account Number</strong><br>{{ $business->account_number }}</p>
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
        @if($sale->transaction_type != TransactionType::PENDING)
            <div>
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('stamp.png'))) }}"
                     width="90"
                     alt="Stamp"
                     class="rotated-stamp">
            </div>
        @endif
    </div>

    <div class="footer">
        <p>{{ $business->footer_text }}</p>
        <p>© {{ date('Y') }} {{ $business->copyright }}</p>
    </div>

</div>
</body>
</html>
