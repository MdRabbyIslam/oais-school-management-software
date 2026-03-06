{{-- similar to create, but populated with $payment --}}
@extends('layouts.app')
@section('plugins.Select2', true)

@section('subtitle','Edit Payment')
@section('content_header_title','Edit Payment Receipt')

@section('content_body')
<div class="card mb-4 p-4">
  <form id="payment_form"
        action="{{ route('payments.update.byStudent', $payment) }}"
        method="POST">
    @csrf @method('PUT')

    {{-- Student dropdown --}}
    <div class="mb-4">
      <label class="form-label">Student</label>
      <select name="student_id" id="student_select"
              class="form-select select2" data-placeholder="Search student…">
        <option></option>
        @foreach($students as $stu)
          <option value="{{ $stu->id }}"
            {{ old('student_id', $payment->student_id) == $stu->id ? 'selected':'' }}>
            {{ $stu->student_id }} – {{ $stu->name }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- show errors --}}
    @if($errors->any())
      <div class="alert alert-danger"><ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul></div>
    @endif

    {{-- Dues table --}}
    <div id="dues_section">
      <table class="table table-bordered mb-4">
        <thead>
          <tr>
            <th>Invoice #</th><th>Description</th>
            <th class="text-end">Due</th>
            <th class="text-end">Pay</th>
          </tr>
        </thead>
        <tbody id="dues_body">
          @foreach($payment->allocations as $alloc)
          @php
            $due = round($alloc->invoiceItem->amount
                   - $alloc->invoiceItem->paymentAllocations
                       ->where('payment_id','!=',$payment->id)
                       ->sum('amount'),2);
          @endphp
          <tr>
            <td>{{ $alloc->invoice->invoice_number }}</td>
            <td>{{ $alloc->invoiceItem->description }}</td>
            <td class="text-end">{{ number_format($due,2) }}</td>
            <td class="text-end">
              <input type="number"
                     name="allocations[{{ $alloc->invoice_item_id }}]"
                     min="0" max="{{ $due }}"
                     step="0.01"
                     class="form-control text-end"
                     value="{{ old("allocations.{$alloc->invoice_item_id}", $alloc->amount) }}">
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>

      {{-- Payment meta --}}
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label>Date</label>
          <input type="date" name="payment_date" class="form-control"
                 value="{{ old('payment_date',$payment->payment_date->toDateString()) }}">
        </div>
        <div class="col-md-4">
          <label>Method</label>
          <select name="payment_method" class="form-select form-control">
            @foreach(['cash','card','bank_transfer'] as $m)
            <option value="{{ $m }}"
              {{ old('payment_method',$payment->payment_method)==$m?'selected':'' }}>
              {{ ucfirst(str_replace('_',' ',$m)) }}
            </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label>Reference</label>
          <input type="text" name="transaction_reference" class="form-control"
                 value="{{ old('transaction_reference',$payment->transaction_reference) }}">
        </div>
      </div>
      <div class="mb-4">
        <label>Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes',$payment->notes) }}</textarea>
      </div>

      <button class="btn btn-primary">Update Payment</button>
    </div>
  </form>
</div>
@endsection

@section('js')
<script>
  $(function(){
    // Select2
    $('#student_select').select2({
      placeholder: 'Search student…', allowClear:true, width:'100%'
    }).on('change',function(){
      // rebuild dues table if user can change student
      loadDueItems(this.value);
    });

    // Load the initial dues from server for this payment
    async function loadDueItems(id) {
      if (!id) return;
      const res = await fetch(`/students/${id}/due-items`);
      const items = await res.json();
      let html = '';
      const old = @json(old('allocations',[]));
      items.forEach(i=>{
        const val = old[i.id] ?? '';
        html += `
          <tr>
            <td>${i.invoice_number}</td>
            <td>${i.description}</td>
            <td class="text-end">${i.due.toFixed(2)}</td>
            <td class="text-end">
              <input type="number"
                     name="allocations[${i.id}]"
                     class="form-control text-end"
                     min="0" max="${i.due}"
                     step="0.01"
                     value="${val}">
            </td>
          </tr>`;
      });
      $('#dues_body').html(html);
    }
  });
</script>
@endsection
