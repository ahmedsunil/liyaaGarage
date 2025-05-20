@php
    use Carbon\Carbon;
    use App\Models\Business;
    use Illuminate\Support\Facades\Storage;

    $business = Business::first();
    // Set default values if no business record exists
    $businessName = $business ? $business->name : 'Liyaa Garage';
    $businessAddress = $business ? $business->street_address : 'N/A';
    $businessContact = $business ? $business->contact : 'N/A';
    $businessEmail = $business ? $business->email : 'N/A';
    $businessInvoicePrefix = $business ? $business->invoice_number_prefix : 'INV';

    function getLogoData($business) {
    // Check custom logo
    try {
        if ($business && $business->logo_path && Storage::disk('public')->exists($business->logo_path)) {
            return base64_encode(Storage::disk('public')->get($business->logo_path));
        }
    } catch (\Exception $e) {
        logger()->error("Logo error: ".$e->getMessage());
    }

    // We'll skip trying to load the default logo since it seems to be missing
    // and causing errors. The template already has a fallback image URL.

    return null;
}

// Usage
$logoData = getLogoData($business);

@endphp
    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Sales Report - {{ $businessName }}</title>
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
<div class="invoice-container">
    <div class="header">
        <div class="invoice-title">
            <h1>SALES REPORT</h1>
            <p>#{{ $businessInvoicePrefix }}/{{ date('Y') }}/{{ str_pad($sales->count(), 4, '0', STR_PAD_LEFT) }}</p>
            <!-- Debug info: {{ $sales->count() }} sales found -->
        </div>
        <div class="company-info">
            @if($logoData)
                <img src="data:image/jpeg;base64,{{ $logoData }}" width="60" alt="Logo">
            @else
                <img src="https://i.postimg.cc/mkyTWP3t/photo-2025-05-14-23-04-56.jpg" width="60" alt="Logo">
            @endif
            <h2>{{ $businessName }}</h2>
            <p>{{ $businessAddress }}</p>
            <p>{{ $businessContact }}</p>
            <p>{{ $businessEmail }}</p>
        </div>
    </div>
    <div class="info-container">
        <div class="info-section">
            <h3>Report Information</h3>
            {{-- <p><strong>Report Name:</strong> {{ $reportName }}</p> --}}
            <p><strong>From Date:</strong> {{ Carbon::parse($fromDate)->format('d/m/Y') }}</p>
            <p><strong>To Date:</strong> {{ Carbon::parse($toDate)->format('d/m/Y') }}</p>
        </div>
        <div class="info-section">
            <h3>Summary</h3>
            <p><strong>Total Sales:</strong> {{ $sales->count() }}</p>
            <p><strong>Total Cash:</strong> {{ number_format($totalCash, 2) }}</p>
            <p><strong>Total Transfer:</strong> {{ number_format($totalTransfer, 2) }}</p>
            <p><strong>Total Pending:</strong> {{ number_format($totalPending, 2) }}</p>
            <p><strong>Total Amount:</strong> {{ number_format($totalAmount, 2) }}</p>
        </div>
        <div class="info-section">
            <h3>Generated</h3>
            <p><strong>Date:</strong> {{ Carbon::now()->format('d/m/Y') }}</p>
            <p><strong>Time:</strong> {{ Carbon::now()->format('H:i:s') }}</p>
        </div>
    </div>

    <h2>Sales Details</h2>
    <table>
        <thead>
        <tr>
            <th>Sale ID</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Transaction Type</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse($sales as $sale)
            <tr>
                <td>{{ $sale->id }}</td>
                <td>{{ Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                <td>{{ $sale->customer ? $sale->customer->name : 'N/A' }}</td>
                <td>{{ $sale->transaction_type }}</td>
                <td>{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center;">No sales found for this period</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="total-section">
        <h3>Total Sales: {{ number_format($totalAmount, 2) }}</h3>
    </div>
</div>
</body>
</html>
