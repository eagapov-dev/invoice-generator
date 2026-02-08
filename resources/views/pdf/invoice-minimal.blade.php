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
            font-size: 11px;
            line-height: 1.6;
            color: #374151;
        }
        .container {
            padding: 50px;
        }
        .header {
            margin-bottom: 50px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 30px;
        }
        .header table {
            width: 100%;
        }
        .logo {
            max-width: 120px;
            max-height: 60px;
        }
        .invoice-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #9ca3af;
            font-weight: 600;
        }
        .invoice-number-large {
            font-size: 24px;
            font-weight: 300;
            color: #111827;
            margin-top: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
            border: 1px solid;
        }
        .status-draft { color: #6b7280; border-color: #d1d5db; }
        .status-sent { color: #2563eb; border-color: #93c5fd; }
        .status-paid { color: #16a34a; border-color: #86efac; }
        .status-overdue { color: #dc2626; border-color: #fca5a5; }
        .parties {
            margin-bottom: 40px;
        }
        .parties table {
            width: 100%;
        }
        .party-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #9ca3af;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .party-name {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 5px;
        }
        .party-detail {
            color: #6b7280;
            margin-bottom: 2px;
            font-size: 11px;
        }
        .dates {
            margin-bottom: 40px;
        }
        .dates table {
            width: auto;
        }
        .dates td {
            padding: 4px 0;
        }
        .dates .label {
            color: #9ca3af;
            padding-right: 30px;
            font-size: 11px;
        }
        .dates .value {
            color: #111827;
            font-weight: 500;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            padding: 12px 0;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
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
            padding: 14px 0;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }
        .items-table tr:last-child td {
            border-bottom: 1px solid #e5e7eb;
        }
        .totals {
            width: 250px;
            margin-left: auto;
            margin-bottom: 50px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 6px 0;
            font-size: 11px;
            color: #6b7280;
        }
        .totals td:last-child {
            text-align: right;
            color: #374151;
        }
        .totals .total-row td {
            padding-top: 12px;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            border-top: 1px solid #e5e7eb;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #9ca3af;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .section-content {
            color: #6b7280;
            font-size: 11px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #d1d5db;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
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

    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        @if($company && $company->logo)
                            <img src="{{ storage_path('app/public/' . $company->logo) }}" class="logo" alt="Logo">
                        @endif
                    </td>
                    <td style="width: 50%; text-align: right; vertical-align: top;">
                        <p class="invoice-title">Invoice</p>
                        <p class="invoice-number-large">{{ $invoice->invoice_number }}</p>
                        <span class="status-badge status-{{ $invoice->status->value }}">
                            {{ $invoice->status->label() }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="parties">
            <table>
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <p class="party-label">From</p>
                        @if($company)
                            <p class="party-name">{{ $company->company_name ?? 'Your Company' }}</p>
                            @if($company->address)
                                <p class="party-detail">{!! nl2br(e($company->address)) !!}</p>
                            @endif
                            @if($company->phone)
                                <p class="party-detail">{{ $company->phone }}</p>
                            @endif
                            @if($company->email)
                                <p class="party-detail">{{ $company->email }}</p>
                            @endif
                        @else
                            <p class="party-name">Your Company</p>
                        @endif
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        <p class="party-label">Bill To</p>
                        <p class="party-name">{{ $client->name }}</p>
                        @if($client->company)
                            <p class="party-detail">{{ $client->company }}</p>
                        @endif
                        @if($client->address)
                            <p class="party-detail">{!! nl2br(e($client->address)) !!}</p>
                        @endif
                        @if($client->email)
                            <p class="party-detail">{{ $client->email }}</p>
                        @endif
                        @if($client->phone)
                            <p class="party-detail">{{ $client->phone }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="dates">
            <table>
                <tr>
                    <td class="label">Invoice Date</td>
                    <td class="value">{{ $invoice->created_at->format('M d, Y') }}</td>
                </tr>
                @if($invoice->due_date)
                <tr>
                    <td class="label">Due Date</td>
                    <td class="value">{{ $invoice->due_date->format('M d, Y') }}</td>
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
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td>{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->tax_percent > 0)
                <tr>
                    <td>Tax ({{ number_format($invoice->tax_percent, 1) }}%)</td>
                    <td>{{ $invoice->currency }} {{ number_format($invoice->subtotal * $invoice->tax_percent / 100, 2) }}</td>
                </tr>
                @endif
                @if($invoice->discount > 0)
                <tr>
                    <td>Discount</td>
                    <td>-{{ $invoice->currency }} {{ number_format($invoice->discount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total</td>
                    <td>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($invoice->notes)
        <div class="section">
            <p class="section-label">Notes</p>
            <p class="section-content">{!! nl2br(e($invoice->notes)) !!}</p>
        </div>
        @endif

        @if($company && $company->bank_details)
        <div class="section">
            <p class="section-label">Payment Details</p>
            <p class="section-content">{!! nl2br(e($company->bank_details)) !!}</p>
        </div>
        @endif

        <div class="footer">
            <p>Thank you for your business</p>
        </div>
    </div>
</body>
</html>
