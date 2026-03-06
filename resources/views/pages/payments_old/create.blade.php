@extends('layouts.app')

@section('subtitle', 'Assign Fees')
@section('content_header_title', 'Record Payment for Invoice')
@section('content_header_subtitle')
<span> #{{ $invoice->invoice_number }}</span>
@endsection

@section('content_body')
    <div class="card">
   
        <div class="card-header">
            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-secondary">Back to Invoice</a>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Student Information</h5>
                    <p><strong>Name:</strong> {{ $invoice->student->name }}</p>
                    <p><strong>Class:</strong> {{ $invoice->student->section->schoolClass->name }} - {{ $invoice->student->section->section_name }}</p>
                </div>
                <div class="col-md-6">
                    <h5>Invoice Summary</h5>
                    <p><strong>Total Amount:</strong> {{ number_format($invoice->total_amount, 2) }}</p>
                    <p><strong>Paid Amount:</strong> {{ number_format($invoice->paid_amount, 2) }}</p>
                    <p><strong>Balance Due:</strong> {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</p>
                </div>
            </div>

            <form action="{{ route('payments.store', $invoice) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_date">Payment Date *</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                   value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount *</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   min="0.01" max="{{ $invoice->total_amount - $invoice->paid_amount }}" 
                                   step="0.01" value="{{ old('amount') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method *</label>
                            <select class="form-control" id="payment_method" name="payment_method" required>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" {{ old('payment_method') == $method ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $method)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="transaction_reference">Transaction Reference</label>
                            <input type="text" class="form-control" id="transaction_reference" 
                                   name="transaction_reference" value="{{ old('transaction_reference') }}">
                            <small class="text-muted">For bank transfers, mobile money, etc.</small>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="allocate_to_items" 
                               name="allocate_to_items" value="1" {{ old('allocate_to_items') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="allocate_to_items">Allocate payment to specific fees</label>
                    </div>
                </div>

                <div id="allocation-section" style="{{ old('allocate_to_items') ? '' : 'display: none;' }}">
                    <h5>Allocate Payment to Fees</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fee Description</th>
                                    <th>Amount Due</th>
                                    <th>Amount Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ number_format($item->amount, 2) }}</td>
                                    <td>
                                        <input type="hidden" name="allocations[{{ $loop->index }}][item_id]" value="{{ $item->id }}">
                                        <input type="number" class="form-control allocation-amount" 
                                               name="allocations[{{ $loop->index }}][amount]" 
                                               min="0" max="{{ $item->amount }}" step="0.01" 
                                               value="{{ old('allocations.'.$loop->index.'.amount', 0) }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Record Payment</button>
            </form>
        </div>
    </div>

@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle allocation section
        const allocateCheckbox = document.getElementById('allocate_to_items');
        const allocationSection = document.getElementById('allocation-section');
        
        allocateCheckbox.addEventListener('change', function() {
            allocationSection.style.display = this.checked ? 'block' : 'none';
        });

        // Validate allocation amounts don't exceed payment amount
        const amountInput = document.getElementById('amount');
        const allocationInputs = document.querySelectorAll('.allocation-amount');
        
        amountInput.addEventListener('input', validateAllocations);
        allocationInputs.forEach(input => {
            input.addEventListener('input', validateAllocations);
        });
        
        function validateAllocations() {
            if (!allocateCheckbox.checked) return;
            
            const totalPayment = parseFloat(amountInput.value) || 0;
            let allocatedTotal = 0;
            
            allocationInputs.forEach(input => {
                allocatedTotal += parseFloat(input.value) || 0;
            });
            
            if (allocatedTotal > totalPayment) {
                alert('Total allocated amount cannot exceed payment amount');
                allocateCheckbox.checked = false;
                allocationSection.style.display = 'none';
            }
        }
    });
</script>
@endsection