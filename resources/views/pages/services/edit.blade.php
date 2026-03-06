@extends('layouts.app')

@section('title', 'Add Fee to Invoice')

@section('content_header_title', 'Add Fee to Invoice #{{ $invoice->id }}')

@section('content_header_subtitle', 'View and add fees to the invoice')

@section('content_body')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add Fee to Invoice #{{ $invoice->id }}</h3>
        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-secondary btn-sm float-right">Back to Invoice</a>
    </div>
    <div class="card-body">
        <!-- Current Fees in Invoice -->
        <h5>Current Fees in Invoice</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Fee Name</th>
                    <th>Amount Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->invoiceFees as $invoiceFee)
                    <tr>
                        <td>{{ $invoiceFee->fee->fee_name }}</td>
                        <td>${{ number_format($invoiceFee->amount_due, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Add New Fee -->
        <form action="{{ route('invoices.add_fee', $invoice->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="fee_id">Select Fee</label>
                <select name="fee_id" id="fee_id" class="form-control" required>
                    <option value="">Select Fee</option>
                    @foreach ($availableFees as $fee)
                        <option value="{{ $fee->id }}" data-amount="{{ $fee->amount }}">
                            {{ $fee->fee_name }} - ${{ number_format($fee->amount, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Auto-filled Amount -->
            <div class="form-group">
                <label for="amount_due">Amount Due</label>
                <input type="text" name="amount_due" id="amount_due" class="form-control" readonly>
            </div>

            <button type="submit" class="btn btn-success">Add Fee to Invoice</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<!-- Script to Auto-fill Amount Based on Selected Fee -->
@push('js')
<script>
    document.getElementById('fee_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const amount = selectedOption.getAttribute('data-amount');
        document.getElementById('amount_due').value = amount ? `$${parseFloat(amount).toFixed(2)}` : '';
    });
</script>
@endpush
@endsection
