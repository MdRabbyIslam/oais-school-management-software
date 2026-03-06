<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #{{ $payment->receipt_number }}</title>
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3a7bd5;
            padding-bottom: 15px;
        }
        .logo {
            width: 80px;
            margin-right: 20px;
        }
        .school-info {
            flex-grow: 1;
            text-align: center;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a5276;
            margin-bottom: 5px;
        }
        .receipt-title {
            font-size: 16px;
            margin: 15px 0;
            color: #2874a6;
            text-align: center;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-table .label {
            font-weight: bold;
            width: 30%;
            color: #2c3e50;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .payment-table th {
            background-color: #3498db;
            color: white;
            text-align: left;
            padding: 8px;
        }
        .payment-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .payment-table tr:nth-child(even) {
            background-color: #f2f9ff;
        }
        .totals-table {
            width: 300px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .grand-total {
            font-weight: bold;
            color: #e74c3c;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 10px;
            text-align: center;
            color: #7f8c8d;
        }
        .watermark {
            position: fixed;
            bottom: 50%;
            right: 50%;
            transform: translate(50%, 50%);
            opacity: 0.1;
            font-size: 72px;
            color: #ccc;
            z-index: -1;
        }
        .signature-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Header with Logo and School Info -->
    <div class="header">
        <div class="logo">
            <img src="{{ storage_path('app/public/' . config('app.school_logo_path')) }}" style="max-width: 80px; max-height: 80px;">
        </div>
        <div class="school-info">
            <div class="school-name">{{ config('app.school_name',"Oasis Model School") }}</div>
            <div>{{ config('app.school_address',"Address") }}</div>
            <div>Phone: {{ config('app.school_phone') }} | Email: {{ config('app.school_email') }}</div>
        </div>
    </div>

    <div class="receipt-title">OFFICIAL PAYMENT RECEIPT</div>
    <div class="watermark">PAID</div>

    <!-- Payment Information -->
    <table class="info-table">
        <tr>
            <td class="label">Receipt Number:</td>
            <td>{{ $payment->receipt_number }}</td>
            <td class="label">Payment Date:</td>
            <td>{{ $payment->payment_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Student Name:</td>
            <td>{{ $payment->student->name }}</td>
            <td class="label">Invoice Number:</td>
            <td>{{ $payment->invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td class="label">Class/Section:</td>
            <td>{{ $payment->student->section->schoolClass->name }} - {{ $payment->student->section->section_name }}</td>
            <td class="label">Payment Method:</td>
            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
        </tr>
        @if($payment->transaction_reference)
        <tr>
            <td class="label">Reference:</td>
            <td colspan="3">{{ $payment->transaction_reference }}</td>
        </tr>
        @endif
    </table>

    <!-- Payment Details -->
    <table class="payment-table">
        <thead>
            <tr>
                <th width="60%">Fee Description</th>
                <th width="20%">Amount Due</th>
                <th width="20%">Amount Paid</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payment->allocations as $allocation)
            <tr>
                <td>{{ $allocation->invoiceItem ? $allocation->invoiceItem->description : 'General Payment' }}</td>
                <td style="text-align: right;">{{ number_format($allocation->invoiceItem ? $allocation->invoiceItem->amount : $allocation->amount, 2) }}</td>
                <td style="text-align: right;">{{ number_format($allocation->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Payment Summary -->
    <table class="totals-table">
        <tr>
            <td class="label">Total Amount:</td>
            <td style="text-align: right;">{{ number_format($payment->invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Previously Paid:</td>
            <td style="text-align: right;">{{ number_format($payment->invoice->paid_amount - $payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">This Payment:</td>
            <td style="text-align: right;">{{ number_format($payment->amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Paid:</td>
            <td style="text-align: right;">{{ number_format($payment->invoice->paid_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Balance Due:</td>
            <td style="text-align: right;">{{ number_format($payment->invoice->total_amount - $payment->invoice->paid_amount, 2) }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <!-- Signature Area -->
    <div class="signature-area">
        <div class="signature-line">Cashier's Signature</div>
        <div class="signature-line">Parent/Guardian Signature</div>
        <div class="signature-line">School Stamp</div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>Note:</strong> This is an official computer-generated receipt. Please retain for your records.<br>
        For any queries, please contact {{ config('app.school_email') }} or call {{ config('app.school_phone') }}
    </div>
</body>
</html>