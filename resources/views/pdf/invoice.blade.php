<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .container {
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .logo {
            max-width: 150px;
            max-height: 80px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 28px;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .info-box p {
            margin-bottom: 3px;
        }
        .info-box .company-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .meta-info {
            margin-bottom: 30px;
        }
        .meta-info table {
            width: 250px;
        }
        .meta-info td {
            padding: 5px 0;
        }
        .meta-info td:first-child {
            color: #666;
        }
        .meta-info td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        .items-table th:nth-child(3),
        .items-table th:nth-child(4),
        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: right;
        }
        .items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 40px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px 0;
        }
        .totals td:last-child {
            text-align: right;
        }
        .totals .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
        }
        .totals .total-row td {
            padding-top: 12px;
        }
        .notes {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .notes h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
        }
        .bank-details {
            background-color: #eff6ff;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
        }
        .bank-details h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background-color: #f1f5f9; color: #64748b; }
        .status-sent { background-color: #dbeafe; color: #2563eb; }
        .status-paid { background-color: #dcfce7; color: #16a34a; }
        .status-overdue { background-color: #fee2e2; color: #dc2626; }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <table style="width: 100%; margin-bottom: 40px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    @if($company && $company->logo)
                        <img src="{{ storage_path('app/public/' . $company->logo) }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <h1 style="font-size: 28px; color: #2563eb; margin-bottom: 5px;">INVOICE</h1>
                    <p class="invoice-number">{{ $invoice->invoice_number }}</p>
                    <span class="status-badge status-{{ $invoice->status->value }}">
                        {{ $invoice->status->label() }}
                    </span>
                </td>
            </tr>
        </table>

        <div class="info-section">
            <div class="info-box" style="float: left; width: 50%;">
                <h3>From</h3>
                @if($company)
                    <p class="company-name">{{ $company->company_name ?? 'Your Company' }}</p>
                    @if($company->address)
                        <p>{!! nl2br(e($company->address)) !!}</p>
                    @endif
                    @if($company->phone)
                        <p>{{ $company->phone }}</p>
                    @endif
                    @if($company->email)
                        <p>{{ $company->email }}</p>
                    @endif
                @else
                    <p class="company-name">Your Company</p>
                @endif
            </div>
            <div class="info-box" style="float: right; width: 50%; text-align: right;">
                <h3>Bill To</h3>
                <p class="company-name">{{ $client->name }}</p>
                @if($client->company)
                    <p>{{ $client->company }}</p>
                @endif
                @if($client->address)
                    <p>{!! nl2br(e($client->address)) !!}</p>
                @endif
                @if($client->email)
                    <p>{{ $client->email }}</p>
                @endif
                @if($client->phone)
                    <p>{{ $client->phone }}</p>
                @endif
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="meta-info">
            <table>
                <tr>
                    <td>Invoice Date:</td>
                    <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                </tr>
                @if($invoice->due_date)
                <tr>
                    <td>Due Date:</td>
                    <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                </tr>
                @endif
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 15%;">Qty</th>
                    <th style="width: 20%;">Price</th>
                    <th style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: right;">{{ number_format($item->quantity, 2) }}</td>
                    <td style="text-align: right;">{{ $company?->default_currency ?? 'USD' }} {{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right;">{{ $company?->default_currency ?? 'USD' }} {{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td>{{ $company?->default_currency ?? 'USD' }} {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->tax_percent > 0)
                <tr>
                    <td>Tax ({{ number_format($invoice->tax_percent, 1) }}%):</td>
                    <td>{{ $company?->default_currency ?? 'USD' }} {{ number_format($invoice->subtotal * $invoice->tax_percent / 100, 2) }}</td>
                </tr>
                @endif
                @if($invoice->discount > 0)
                <tr>
                    <td>Discount:</td>
                    <td>-{{ $company?->default_currency ?? 'USD' }} {{ number_format($invoice->discount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total:</td>
                    <td>{{ $company?->default_currency ?? 'USD' }} {{ number_format($invoice->total, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($invoice->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{!! nl2br(e($invoice->notes)) !!}</p>
        </div>
        @endif

        @if($company && $company->bank_details)
        <div class="bank-details">
            <h3>Payment Details</h3>
            <p>{!! nl2br(e($company->bank_details)) !!}</p>
        </div>
        @endif

        <div class="footer">
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>
</html>
