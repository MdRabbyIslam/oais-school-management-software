@extends('layouts.app')

@section('content_header_title', 'Invoice Details')
@section('content_header_subtitle', $invoice->invoice_number)

@section('content_body')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between">
            <h3 class="card-title">Invoice #{{ $invoice->invoice_number }}</h3>
            <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-primary">
                <i class="fas fa-file-pdf mr-2"></i>Download PDF
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Student Information</h5>
                <p>
                    <strong>{{ $invoice->student->name }}</strong><br>
                    Class: {{ $invoice->student->section->schoolClass->name }}<br>
                    Section: {{ $invoice->student->section->section_name }}
                </p>
            </div>
            <div class="col-md-6 text-right">
                <h5>Invoice Details</h5>
                <p>
                    <strong>Date:</strong> {{ $invoice->invoice_date->format('M d, Y') }}<br>
                    <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}<br>
                    <strong>Status:</strong>
                    <span class="badge bg-{{
                        $invoice->status == 'paid' ? 'success' :
                        ($invoice->status == 'overdue' ? 'danger' : 'warning')
                    }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td class="text-right"><strong>Total:</strong></td>
                        <td class="text-right"><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-right"><strong>Total Paid:</strong></td>
                        <td class="text-right"><strong>{{ number_format($invoice->paid_amount, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-right"><strong>Total Due:</strong></td>
                        <td class="text-right"><strong>{{ number_format(($invoice->total_amount - $invoice->paid_amount), 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer text-right">
        @if($invoice->status != 'paid')

        <a href="{{ route('invoices.payments.create', $invoice) }}"
                class="btn btn-sm btn-success"
                title="Record Payment">
                <i class="fas fa-money-bill-wave"></i> Record Payment
        </a>
        @endif
    </div>
</div>

<!-- Payment Modal (Optional) -->
{{-- <div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('payments.store', $invoice) }}">
            <form method="POST" action="#">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" class="form-control"
                               name="amount" value="{{ $invoice->total_amount - $invoice->paid_amount }}" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="method" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div> --}}
@endsection
