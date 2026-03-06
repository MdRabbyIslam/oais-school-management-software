@extends('layouts.app')

@section('subtitle', 'Payments')
@section('content_header_title', 'Payment List')

@section('content_body')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Payment List</h3>
            <form method="GET" action="{{ route('payments.index') }}" class="form-inline">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm mx-2" placeholder="Search receipt or student">

                <select name="method" class="form-select form-control-sm mx-2">
                    <option value="">All Methods</option>
                    <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="card" {{ request('method') == 'card' ? 'selected' : '' }}>Card</option>
                    <option value="bank_transfer" {{ request('method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                </select>

                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm mx-2">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm mx-2">

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-secondary ml-2">
                    <i class="fas fa-history"></i> Reset
                </a>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Receipt #</th>
                    <th>Student</th>
                    <th>Invoice(s)</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $invoices = $payment->allocations->pluck('invoice.invoice_number')->unique()->implode(', ');
                    @endphp
                    <tr>
                        <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                        <td>{{ $payment->receipt_number }}</td>
                        <td>{{ $payment->student->name }}</td>
                        <td>{{ $invoices }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td class="text-end">&#36;{{ number_format($payment->amount, 2) }}</td>
                        <td class="text-center">
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-secondary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('payments.edit.byStudent', $payment) }}" class="btn btn-sm btn-info" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>

                            <a href="{{ route('payments.print.html', $payment) }}" class="btn btn-sm btn-secondary" target="_blank" title="Print Receipt">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="{{ route('payments.printHTMLBn', $payment) }}" class="btn btn-sm btn-primary" target="_blank" title="Print Receipt (Bengali)">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix">
        {{ $payments->links() }}
    </div>
</div>
@endsection

@section('js')
<script>
    // Optional: enhance if needed
</script>
@endsection
