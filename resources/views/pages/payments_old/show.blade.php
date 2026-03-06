@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Payment Receipt #{{ $payment->receipt_number }}</h4>
            <div>
                <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-file-pdf"></i> Download Receipt
                </a>
                <a href="{{ route('invoices.show', $payment->invoice) }}" class="btn btn-sm btn-secondary">
                    View Invoice
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Payment Details</h5>
                    <p><strong>Date:</strong> {{ $payment->payment_date->format('d M Y') }}</p>
                    <p><strong>Amount:</strong> {{ number_format($payment->amount, 2) }}</p>
                    <p><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                    @if($payment->transaction_reference)
                        <p><strong>Reference:</strong> {{ $payment->transaction_reference }}</p>
                    @endif
                    <p><strong>Recorded By:</strong> {{ $payment->recorder->name }}</p>
                </div>
                <div class="col-md-6">
                    <h5>Student Information</h5>
                    <p><strong>Name:</strong> {{ $payment->student->name }}</p>
                    <p><strong>Class:</strong> {{ $payment->student->section->schoolClass->name }} - {{ $payment->student->section->section_name }}</p>
                    <p><strong>Student ID:</strong> {{ $payment->student->student_id }}</p>
                </div>
            </div>

            <h5>Payment Allocation</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Fee Description</th>
                            <th>Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($payment->allocations->count() > 0)
                            @foreach($payment->allocations as $allocation)
                            <tr>
                                <td>{{ $allocation->invoiceItem ? $allocation->invoiceItem->description : 'General Payment' }}</td>
                                <td>{{ number_format($allocation->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>General Payment</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="table-active">
                            <th>Total Paid</th>
                            <th>{{ number_format($payment->amount, 2) }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($payment->notes)
                <div class="alert alert-info mt-4">
                    <h6>Notes:</h6>
                    <p>{{ $payment->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection