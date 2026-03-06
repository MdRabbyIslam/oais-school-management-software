@extends('layouts.app')

@section('subtitle', 'Assign Fees')
@section('content_header_title', 'Record Payment for Invoice')
@section('content_header_subtitle')
<span>#{{ $invoice->invoice_number }}</span>
@endsection

@section('content_body')
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="mb-1">Student</h6>
                <p class="mb-0">{{ $invoice->student->name }}</p>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="mb-1">Invoice Total</h6>
                <p class="mb-0">&#36;{{ number_format($invoice->total_amount, 2) }}</p>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="mb-1">Amount Paid</h6>
                <p class="mb-0">&#36;{{ number_format($invoice->payments->sum('amount'), 2) }}</p>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="mb-1">Balance Due</h6>
                <p class="mb-0">&#36;{{ number_format($invoice->total_amount - $invoice->payments->sum('amount'), 2) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- show erros --}}

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('invoices.payments.store', $invoice) }}" method="POST">
    @csrf
    <div class="card mb-4">
        <div class="card-header">Allocate to Line Items</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount Due</th>
                        <th class="text-end">Allocate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        @php
                            $paid = optional($item->paymentAllocations)->sum('amount') ?? 0;
                            $due  = $item->amount - $paid;
                        @endphp
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="text-end">&#36;{{ number_format($due, 2) }}</td>
                            <td class="text-end">
                                @if($due > 0)
                                    <input
                                        type="number"
                                        name="allocations[{{ $item->id }}]"
                                        class="form-control text-end allocation-input"
                                        step="0.01"
                                        min="0"
                                        max="{{ $due }}"
                                        value="{{ old('allocations.' . $item->id, $due) }}"
                                    >
                                @else
                                    <span class="badge bg-success">Paid</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="payment_date" class="form-label">Payment Date</label>
                    <input
                        type="date"
                        id="payment_date"
                        name="payment_date"
                        class="form-control"
                        value="{{ old('payment_date', now()->toDateString()) }}"
                    >
                </div>
                <div class="col-md-4">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control">
                        <option value="">Choose…</option>
                        <option value="cash" {{ old('payment_method')=='cash'?'selected':'' }}>Cash</option>
                        <option value="card" {{ old('payment_method')=='card'?'selected':'' }}>Card</option>
                        <option value="bank_transfer" {{ old('payment_method')=='bank_transfer'?'selected':'' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="receipt_number" class="form-label">Receipt #</label>
                    <input
                        type="text"
                        id="receipt_number"
                        name="receipt_number"
                        class="form-control"
                        readonly
                        value="{{ old('receipt_number', $receiptNumber) }}"
                    >
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="amount" class="form-label">Total Paid</label>
                    <input
                        type="text"
                        id="amount"
                        name="amount"
                        class="form-control"
                        placeholder="Sum of allocations"
                        value="{{ old('amount') }}"
                        readonly
                    >
                </div>
                <div class="col-md-6">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="form-control"
                    >{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">Record Payment</button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const allocationInputs = document.querySelectorAll('.allocation-input');
    const totalPaidInput = document.getElementById('amount');

    function updateTotal() {
        let sum = 0;
        allocationInputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            const due = parseFloat(input.getAttribute('max')) || 0;

            // Enforce that the only valid inputs are 0 or the full due amount
            if (val !== 0 && val !== due) {
                // Reset invalid entry back to 0
                input.value = '0.00';
            }

            sum += parseFloat(input.value) || 0;
        });
        totalPaidInput.value = sum.toFixed(2);
    }

    allocationInputs.forEach(input => {
        // Whenever the user types or changes, recalc and enforce rules
        input.addEventListener('input', updateTotal);
        // Also enforce once on blur in case they paste
        input.addEventListener('blur', updateTotal);
    });

    // Initialize on load
    updateTotal();
});
</script>
@endsection
