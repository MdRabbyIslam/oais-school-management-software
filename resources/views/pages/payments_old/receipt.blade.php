<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        .header { display: flex; margin-bottom: 20px; }
        .logo { width: 80px; margin-right: 20px; }
        .school-info { flex-grow: 1; text-align: center; }
        .school-name { font-size: 18px; font-weight: bold; }
        .receipt-title { font-size: 16px; margin: 15px 0; text-align: center; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px 0; }
        .info-table .label { font-weight: bold; width: 30%; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 5px; }
        .signature-area { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-line { width: 200px; border-top: 1px solid #000; text-align: center; padding-top: 5px; }
        .watermark { position: fixed; bottom: 50%; right: 50%; transform: translate(50%, 50%); 
                    opacity: 0.1; font-size: 72px; color: #ccc; z-index: -1; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="{{ storage_path('app/public/' . config('app.school_logo_path')) }}" style="max-width: 80px;">
        </div>
        <div class="school-info">
            <div class="school-name">{{ config('app.school_name') }}</div>
            <div>{{ config('app.school_address') }}</div>
        </div>
    </div>

    <div class="receipt-title">OFFICIAL PAYMENT RECEIPT</div>
    <div class="watermark">PAID</div>

    <table class="info-table">
        <tr>
            <td class="label">Receipt No:</td>
            <td>{{ $payment->receipt_number }}</td>
            <td class="label">Date:</td>
            <td>{{ $payment->payment_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Student:</td>
            <td>{{ $payment->student->name }}</td>
            <td class="label">Invoice No:</td>
            <td>{{ $payment->invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td class="label">Class:</td>
            <td>{{ $payment->student->section->schoolClass->name }}</td>
            <td class="label">Payment Method:</td>
            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @if($payment->allocations->count() > 0)
                @foreach($payment->allocations as $allocation)
                <tr>
                    <td>{{ $allocation->invoiceItem ? $allocation->invoiceItem->description : 'General Payment' }}</td>
                    <td style="text-align: right;">{{ number_format($allocation->amount, 2) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td>Payment towards invoice {{ $payment->invoice->invoice_number }}</td>
                    <td style="text-align: right;">{{ number_format($payment->amount, 2) }}</td>
                </tr>
            @endif
            <tr style="font-weight: bold;">
                <td>TOTAL PAID</td>
                <td style="text-align: right;">{{ number_format($payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-area">
        <div class="signature-line">Cashier</div>
        <div class="signature-line">Parent/Guardian</div>
        <div class="signature-line">School Stamp</div>
    </div>

    <div style="margin-top: 20px; text-align: center; font-size: 10px;">
        Computer generated receipt - Valid without signature
    </div>
</body>
</html>