<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th, .table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .header, .footer {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Invoice #{{ $invoice->id }}</h2>
        <p><strong>Student:</strong> {{ $invoice->student->name }}</p>
        <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('Y-m-d') }}</p>
        <p><strong>Due Date:</strong> {{ $invoice->due_date->format('Y-m-d') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Fee Name</th>
                <th>Amount Due</th>
                <th>Amount Paid</th>
                <th>Remaining Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->invoiceFees as $invoiceFee)
                <tr>
                    <td>{{ $invoiceFee->fee->fee_name }}</td>
                    <td>${{ number_format($invoiceFee->amount_due, 2) }}</td>
                    <td>${{ number_format($invoiceFee->amount_paid, 2) }}</td>
                    <td>${{ number_format($invoiceFee->amount_due - $invoiceFee->amount_paid, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Total Due:</strong> ${{ number_format($invoice->total_due, 2) }}</p>
        <p>Status: {{ $invoice->status }}</p>
    </div>
</body>
</html>
