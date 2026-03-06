
<div class="card mb-4">
  <div class="card-body">
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label for="payment_date" class="form-label">Payment Date</label>
        <input
          type="date"
          id="payment_date"
          name="payment_date"
          class="form-control"
          value="{{ old('payment_date', $payment->payment_date->toDateString() ?? now()->toDateString()) }}"
        >
      </div>
      <div class="col-md-4">
        <label for="payment_method" class="form-label">Payment Method</label>
        <select
          id="payment_method"
          name="payment_method"
          class="form-select form-control"
        >
          <option value="">Choose…</option>
          @foreach(['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'] as $key => $label)
            <option value="{{ $key }}" {{ old('payment_method', $payment->payment_method ?? '') === $key ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
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
          value="{{ old('receipt_number', $payment->receipt_number ?? $receiptNumber) }}"
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
          readonly
          value="{{ old('amount', $payment->amount ?? '') }}"
        >
      </div>
      <div class="col-md-6">
        <label for="notes" class="form-label">Notes</label>
        <textarea
          id="notes"
          name="notes"
          rows="3"
          class="form-control"
        >{{ old('notes', $payment->notes ?? '') }}</textarea>
      </div>
    </div>
  </div>
</div>
