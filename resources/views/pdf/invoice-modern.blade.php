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
            line-height: 1.6;
            color: #1e293b;
        }
        .header {
            background-color: #1e293b;
            color: #fff;
            padding: 40px;
        }
        .header table {
            width: 100%;
        }
        .header .logo {
            max-width: 130px;
            max-height: 70px;
        }
        .header h1 {
            font-size: 32px;
            font-weight: 300;
            letter-spacing: 4px;
            margin-bottom: 5px;
        }
        .header .invoice-number {
            font-size: 13px;
            color: #94a3b8;
        }
        .header .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 8px;
        }
        .status-draft { background-color: #475569; color: #cbd5e1; }
        .status-sent { background-color: #1d4ed8; color: #fff; }
        .status-paid { background-color: #15803d; color: #fff; }
        .status-overdue { background-color: #dc2626; color: #fff; }
        .content {
            padding: 40px;
        }
        .info-section {
            margin-bottom: 35px;
        }
        .info-section table {
            width: 100%;
        }
        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info-value {
            margin-bottom: 3px;
            font-size: 12px;
        }
        .info-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            color: #0f172a;
        }
        .meta-bar {
            background-color: #f1f5f9;
            padding: 15px 40px;
            margin-bottom: 0;
        }
        .meta-bar table {
            width: 100%;
        }
        .meta-bar td {
            padding: 0 20px 0 0;
        }
        .meta-bar .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }
        .meta-bar .value {
            font-weight: bold;
            font-size: 13px;
            color: #0f172a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #f8fafc;
            padding: 14px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table th:nth-child(2),
        .items-table th:nth-child(3),
        .items-table th:nth-child(4),
        .items-table td:nth-child(2),
        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: right;
        }
        .items-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .items-table tr:last-child td {
            border-bottom: 2px solid #e2e8f0;
        }
        .totals {
            width: 280px;
            margin-left: auto;
            margin-bottom: 40px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px 0;
            font-size: 12px;
        }
        .totals td:last-child {
            text-align: right;
        }
        .totals .total-row {
            border-top: 2px solid #1e293b;
        }
        .totals .total-row td {
            padding-top: 12px;
            font-weight: bold;
            font-size: 18px;
            color: #1e293b;
        }
        .notes {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .notes h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .bank-details {
            background-color: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #bae6fd;
        }
        .bank-details h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0369a1;
            margin-bottom: 8px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 10%;
            font-size: 60px;
            color: rgba(0, 0, 0, 0.06);
            transform: rotate(-35deg);
            z-index: 0;
            pointer-events: none;
            white-space: nowrap;
            font-weight: bold;
            letter-spacing: 5px;
        }
    </style>
</head>
<body>
    @if($showWatermark)
        <div class="watermark">INVOICE GENERATOR</div>
    @endif

    <div class="header">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    @if($company && $company->logo)
                        <img src="{{ storage_path('app/public/' . $company->logo) }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <h1>INVOICE</h1>
                    <p class="invoice-number">{{ $invoice->invoice_number }}</p>
                    <span class="status-badge status-{{ $invoice->status->value }}">
                        {{ $invoice->status->label() }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="meta-bar">
        <table>
            <tr>
                <td>
                    <div class="label">Invoice Date</div>
                    <div class="value">{{ $invoice->created_at->format('M d, Y') }}</div>
                </td>
                @if($invoice->due_date)
                <td>
                    <div class="label">Due Date</div>
                    <div class="value">{{ $invoice->due_date->format('M d, Y') }}</div>
                </td>
                @endif
                <td>
                    <div class="label">Currency</div>
                    <div class="value">{{ $invoice->currency }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <div class="info-section">
            <table>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <p class="info-label">From</p>
                        @if($company)
                            <p class="info-name">{{ $company->company_name ?? 'Your Company' }}</p>
                            @if($company->address)
                                <p class="info-value">{!! nl2br(e($company->address)) !!}</p>
                            @endif
                            @if($company->phone)
                                <p class="info-value">{{ $company->phone }}</p>
                            @endif
                            @if($company->email)
                                <p class="info-value">{{ $company->email }}</p>
                            @endif
                        @else
                            <p class="info-name">Your Company</p>
                        @endif
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        <p class="info-label">Bill To</p>
                        <p class="info-name">{{ $client->name }}</p>
                        @if($client->company)
                            <p class="info-value">{{ $client->company }}</p>
                        @endif
                        @if($client->address)
                            <p class="info-value">{!! nl2br(e($client->address)) !!}</p>
                        @endif
                        @if($client->email)
                            <p class="info-value">{{ $client->email }}</p>
                        @endif
                        @if($client->phone)
                            <p class="info-value">{{ $client->phone }}</p>
                        @endif
                    </td>
                </tr>
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
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td>{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->tax_percent > 0)
                <tr>
                    <td>Tax ({{ number_format($invoice->tax_percent, 1) }}%):</td>
                    <td>{{ $invoice->currency }} {{ number_format($invoice->subtotal * $invoice->tax_percent / 100, 2) }}</td>
                </tr>
                @endif
                @if($invoice->discount > 0)
                <tr>
                    <td>Discount:</td>
                    <td>-{{ $invoice->currency }} {{ number_format($invoice->discount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total:</td>
                    <td>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
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
