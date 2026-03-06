<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        /* School-themed styling */
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; line-height: 1.5; margin: 0; padding: 0; }

        /* Header with logo */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #3a7bd5;
            margin-bottom: 20px;
            padding-bottom: 15px;
            table-layout: fixed;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-cell {
            width: 80px;       /* fixed width for the logo */
        }
        .info-cell {
            text-align: center;
            width: 100%
        }
        .logo-cell img {
            max-width: 80px;
            max-height: 80px;
        }
        .school-name   { font-size: 22px; font-weight: bold; color: #1a5276; margin-bottom: 5px; }
        .school-motto  { font-style: italic; color: #5d6d7e; font-size: 12px; }
        .school-address{ font-size: 11px; margin-top: 5px; }

        .invoice-title { font-size: 18px; margin: 15px 0; color: #2874a6; text-align: center; }
        .watermark { position: fixed; bottom: 50%; right: 50%; transform: translate(50%, 50%);
                    opacity: 0.1; font-size: 72px; color: #ccc; z-index: -1; }

        /* Information tables */
         .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 8px 0;
            vertical-align: top;
            font-size: 12px;
            color: #333;
        }
        .info-table .label {
            width: 18%;
            font-weight: bold;
            color: #2c3e50;
        }
        .info-table .value {
            width: 28%;
            padding-left: 10px;
        }
        .info-table .label-right {
            width: 22%;
            font-weight: bold;
            color: #2c3e50;
            padding-left: 30px; /* small gutter */
        }
        .info-table .value-right {
            width: 32%;
            padding-left: 10px;
        }
        .info-table tr + tr td {
            border-top: 1px solid #ddd;
        }

        .badge {
            display: inline-block;
            padding: .25em .6em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .25rem;
        }

        .badge-success {
            background-color: #28a745;
            color: #fff;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background-color: #dc3545;
            color: #fff;
        }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table th { background-color: #3498db; color: white; text-align: left; padding: 8px; }
        .items-table td { border: 1px solid #ddd; padding: 8px; }
        .items-table tr:nth-child(even) { background-color: #f2f9ff; }

        /* Totals */
        .totals-table { width: 300px; float: right; border-collapse: collapse; }
        .totals-table td { padding: 8px; border: 1px solid #ddd; }
        .totals-table .label { font-weight: bold; background-color: #f8f9f9; }
        .grand-total { font-weight: bold; color: #e74c3c; font-size: 14px; }

        /* Footer */
        .footer { margin-top: 40px; padding-top: 10px; border-top: 1px solid #eee; font-size: 10px; text-align: center; color: #7f8c8d; }
        .payment-info { background-color: #f9f9f9; padding: 10px; margin-top: 20px; border: 1px dashed #ccc; }

        /* For future receipt version */
        .signature-area { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-line { width: 200px; border-top: 1px solid #000; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>
    <!-- School Header with Logo -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img alt="logo" src="{{ public_path("upload/images/Logo__Oysis.png") }}" style="max-width: 80px; max-height: 80px;">
            </td>
            <td class="info-cell">
                 <div class="school-name">{{ config('app.school_name','Oasis Model School') }}</div>
                <div class="school-motto">"Knowledge, Wisdom, Excellence"</div>
                <div class="school-address">
                    {{ config('app.school_address') }}<br>
                    Phone: {{ config('app.school_phone') }} | Email: {{ config('app.school_email') }}
                </div>
            </td>
            <td class="logo-cell"></td>

        </tr>
    </table>


    <div class="invoice-title">INVOICE</div>

    <!-- Watermark (visible on copy) -->
    <div class="watermark">{{ strtoupper(config('app.school_name', 'Oasis Model School')) }}</div>

    <!-- Student and Invoice Info -->
    <table class="info-table">
        <tr>
            <td class="label">Student Name:</td>
            <td class="value">{{ $invoice->student->name }}</td>
            <td class="label-right">Invoice Number:</td>
            <td class="value-right">{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td class="label">Class/Section:</td>
            <td class="value">
            {{ $invoice->student->section->schoolClass->name }}
            – {{ $invoice->student->section->section_name }}
            </td>
            <td class="label-right">Invoice Date:</td>
            <td class="value-right">{{ $invoice->invoice_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Student ID:</td>
            <td class="value">{{ $invoice->student->student_id }}</td>
            <td class="label-right">Due Date:</td>
            <td class="value-right">{{ $invoice->due_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <!-- Empty left cells to balance the grid -->
            <td class="label"></td>
            <td class="value"></td>
            <td class="label-right">Status:</td>
            <td class="value-right">
                {{-- if status paid show green badge and if status overdue show red badge --}}
                <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'issued' ? 'danger' : 'warning') }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Fee Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="55%">Description</th>
                <th width="15%">Period</th>
                <th width="15%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->feeAssignment->due_date->format('M Y') }}</td>
                <td style="text-align: right;">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td style="text-align: right;">{{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Paid Amount:</td>
            <td style="text-align: right;">{{ number_format($invoice->paid_amount, 2) }}</td>
        </tr>
        @if($invoice->discount > 0)
        <tr>
            <td class="label">Discount:</td>
            <td style="text-align: right;">-{{ number_format($invoice->discount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>Balance Due:</td>
            <td style="text-align: right;">{{ number_format($invoice->total_amount - $invoice->paid_amount - $invoice->discount, 2) }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <!-- Payment Information -->
    <div class="payment-info">
        <strong>Payment Methods:</strong><br>
        1. Bank Transfer: ABC Bank, Account #123456789, Branch: Main Branch<br>
        2. Cash Payment: School Accounts Office (Receipt required)<br>
        3. Mobile Payment: 0712345678 (Include invoice number as reference)
    </div>

    <!-- Footer -->
    <div class="footer">
        <strong>Note:</strong> Late payments incur a 5% monthly penalty. For queries contact accounts@school.edu<br>
        Computer generated invoice - No signature required. Valid without stamp.
    </div>

    <!-- For future receipt version -->
    {{--
    <div class="signature-area">
        <div class="signature-line">Student/Parent Signature</div>
        <div class="signature-line">Cashier Signature</div>
        <div class="signature-line">School Stamp</div>
    </div>
    --}}
</body>
</html>
