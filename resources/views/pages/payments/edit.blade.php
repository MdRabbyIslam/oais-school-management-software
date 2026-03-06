@extends('layouts.app')

@section('subtitle', 'Edit Payment')
@section('content_header_title', 'Edit Payment')
@section('content_header_subtitle')
<span>#{{ $payment->receipt_number }}</span>
@endsection

@section('content_body')
<form action="{{ route('payments.update', $payment) }}" method="POST">
  @csrf
  @method('PUT')

  {{-- Invoice summary --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        {{-- Student, Invoice Total, etc. --}}
      </div>
    </div>
  </div>

  {{-- Allocation table --}}
  <div class="card mb-4">
    <div class="card-header">Allocate to Line Items</div>
    <div class="card-body">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Description</th>
            <th class="text-end">Amount Due Before This Payment</th>
            <th class="text-end">Allocate</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoice->items as $item)
            @php
              // Compute how much was due before this payment:
              $prevPaid = $item->paymentAllocations()
                               ->where('payment_id', '!=', $payment->id)
                               ->sum('amount');
              $dueBefore = $item->amount - $prevPaid;
              // And what this payment had allocated already:
              $currentAlloc = $payment->allocations()
                                      ->where('invoice_item_id', $item->id)
                                      ->sum('amount');
            @endphp
            <tr>
              <td>{{ $item->description }}</td>
              <td class="text-end">&#36;{{ number_format($dueBefore,2) }}</td>
              <td class="text-end">
                @if($dueBefore > 0)
                  <input
                    type="number"
                    name="allocations[{{ $item->id }}]"
                    class="form-control text-end allocation-input"
                    step="0.01"
                    min="0"
                    max="{{ $dueBefore }}"
                    value="{{ old('allocations.'.$item->id, $currentAlloc) }}"
                  >
                @else
                  <span class="badge bg-success">Paid</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Other form fields (date, method, receipt, notes) --}}
  @include('pages.payments._form_fields', ['payment' => $payment])


  {{-- Submit --}}
  <div class="d-flex justify-content-end mb-5">
    <button type="submit" class="btn btn-primary">Update Payment</button>
  </div>
</form>

{{-- The same JS snippet to recalc total Paid --}}
<script>
  document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('.allocation-input');
      const totalPaid = document.getElementById('amount');
      function recalc() {
          let sum = 0;
          inputs.forEach(i => sum += parseFloat(i.value)||0 );
          totalPaid.value = sum.toFixed(2);
      }
      inputs.forEach(i => i.addEventListener('input', recalc));
      recalc();
  });
</script>
@endsection
