@extends('layouts.app')

@section('title', 'Add Fee to Invoice')

@section('content_header_title', 'Add Fee to Invoice')
@section('content_header_subtitle', 'Select a fee and add it to the invoice')

@section('content_body')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add Fee to Invoice: {{ $invoice->id }}</h3>
        <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-secondary btn-sm float-right">Back to Invoice</a>
    </div>
    <div class="card-body">
        <!-- Display success or error messages -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Add Fee Form -->
        <form action="{{ route('invoices.add_fee', $invoice->id) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="fee_id">Select Fee</label>
                <select name="fee_id" id="fee_id" class="form-control" required>
                    <option value="">-- Select Fee --</option>
                    @foreach($fees as $fee)
                        <option value="{{ $fee->id }}">{{ $fee->name }} ({{ number_format($fee->amount, 2) }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" name="due_date" id="due_date" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Add Fee</button>
        </form>
    </div>
</div>
@endsection
