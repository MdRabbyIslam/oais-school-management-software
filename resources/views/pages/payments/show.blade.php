@extends('layouts.app')

@section('subtitle', 'Payments')
@section('content_header_title', 'Payment Receipt')

@section('content_body')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Receipt: {{ $payment->receipt_number }}</h3>
            <div class="card-tools">
                <a href="{{ route('payments.edit.byStudent', $payment) }}" class="btn btn-sm btn-info">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('payments.print.html', $payment) }}" class="btn btn-sm btn-secondary" target="_blank">
                    <i class="fas fa-print"></i> Print
                </a>
            </div>
        </div>

        <div class="card-body">
            <dl class="row mb-4">
                <dt class="col-sm-3">Date</dt>
                <dd class="col-sm-9">{{ $payment->payment_date->format('Y-m-d') }}</dd>

                <dt class="col-sm-3">Student</dt>
                <dd class="col-sm-9">{{ $payment->student->name }} ({{ $payment->student->student_id }})</dd>

                <dt class="col-sm-3">Payment Method</dt>
                <dd class="col-sm-9">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</dd>

                @if($payment->transaction_reference)
                    <dt class="col-sm-3">Reference</dt>
                    <dd class="col-sm-9">{{ $payment->transaction_reference }}</dd>
                @endif

                @if($payment->notes)
                    <dt class="col-sm-3">Notes</dt>
                    <dd class="col-sm-9">{{ $payment->notes }}</dd>
                @endif
            </dl>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment->allocations as $alloc)
                        <tr>
                            <td>{{ $alloc->invoice->invoice_number }}</td>
                            <td>{{ $alloc->invoiceItem->description }}</td>
                            <td class="text-end">{{ number_format($alloc->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end"><strong>Total Paid:</strong></td>
                        <td class="text-end"><strong>{{ number_format($payment->amount, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
