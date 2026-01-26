<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .invoice-info {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            padding: 8px 0;
        }
        .invoice-info td:first-child {
            color: #666;
            width: 40%;
        }
        .invoice-info td:last-child {
            font-weight: bold;
        }
        .total {
            font-size: 20px;
            color: #2563eb;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company?->company_name ?? 'Invoice' }}</div>
    </div>

    <p>Dear {{ $client->name }},</p>

    <p>Please find attached invoice <strong>{{ $invoice->invoice_number }}</strong> for your records.</p>

    <div class="invoice-info">
        <table>
            <tr>
                <td>Invoice Number:</td>
                <td>{{ $invoice->invoice_number }}</td>
            </tr>
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
            <tr>
                <td>Amount Due:</td>
                <td class="total">{{ $company?->default_currency ?? 'USD' }} {{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($company && $company->bank_details)
    <p><strong>Payment Details:</strong></p>
    <p>{!! nl2br(e($company->bank_details)) !!}</p>
    @endif

    <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>

    <p>Thank you for your business!</p>

    <div class="footer">
        <p>
            @if($company)
                {{ $company->company_name }}<br>
                @if($company->address){!! nl2br(e($company->address)) !!}<br>@endif
                @if($company->phone){{ $company->phone }}<br>@endif
                @if($company->email){{ $company->email }}@endif
            @endif
        </p>
    </div>
</body>
</html>
