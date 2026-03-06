{{-- resources/views/pages/payments/create_by_student.blade.php --}}
@extends('layouts.app')

{{-- enable the Select2 plugin --}}
@section('plugins.Select2', true)

@section('subtitle', 'Record Payment')
@section('content_header_title', 'Record Payment')
@section('css')
    <style>
        .table-success.table-active{
            background-color: #c3e6cb;
        }
        .table-active.table-danger, .table-active.table-danger>td, .table-active.table-danger>th {
            background-color: #f5c6cb;
        }
        .table-active.table-warning, .table-active.table-warning>td, .table-active.table-warning>th {
            background-color: #ffeeba;
        }

    </style>
@endsection

@section('content_body')
<div class="card mb-4 p-4">
  {{-- Student selector --}}
  <div class="mb-4">
    <label for="student_select" class="form-label">Student</label>
    <select id="student_select"
            name="student_id"
            class="form-select select2"
            data-placeholder="Search by ID, name, class or section…"
            style="width:100%">
      {{-- if we’re repopulating after error, show that one student --}}
      @if(isset($oldStudent))
        <option value="{{ $oldStudent->id }}" selected>
          {{ $oldStudent->student_id }} – {{ $oldStudent->name }}
          ({{ $oldStudent->section->schoolClass->name }}
           – {{ $oldStudent->section->section_name }})
        </option>
      @endif
    </select>
  </div>

  {{-- Validation errors --}}
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form id="payment_form"
        action="{{ route('payments.store') }}"
        method="POST">
    @csrf

    {{-- keep the selected student in a hidden field --}}
    <input type="hidden" name="student_id" id="student_id"
           value="{{ old('student_id') }}">

    {{-- Dues table (pops up once a student is chosen) --}}
    <div id="dues_section" style="{{ old('student_id') ? '' : 'display:none;' }}">
      {{-- Add spinner and autofill button above the table --}}
      <div class="d-flex justify-content-between align-items-center mb-2">
        <button type="button" id="autofill_btn" class="btn btn-outline-primary btn-sm">
          Auto-Fill Full Payment
        </button>
        <div id="dues_spinner" style="display:none;">
          <span class="spinner-border spinner-border-sm"></span> Loading dues…
        </div>
      </div>

      <table class="table table-bordered mb-4">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Description</th>
            <th class="text-end">Amount Due</th>
            <th class="text-end">Pay</th>
          </tr>
        </thead>
        <tbody id="dues_body">
          {{-- JS will inject rows here --}}
        </tbody>
        <tr id="dues_summary_row" style="display:none;">
          <td colspan="3" class="fw-bold text-end">Total Paid:</td>
          <td class="text-end" id="total_paid_cell">0.00</td>
          <td></td>
        </tr>
        <tr id="dues_remaining_row" style="display:none;">
          <td colspan="3" class="fw-bold text-end">Remaining Due:</td>
          <td class="text-end" id="remaining_due_cell">0.00</td>
          <td></td>
        </tr>
      </table>

      {{-- Payment metadata --}}
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label for="payment_date" class="form-label">Date</label>
          <input type="date"
                 name="payment_date"
                 id="payment_date"
                 class="form-control"
                 value="{{ old('payment_date', now()->toDateString()) }}">
        </div>
        <div class="col-md-4">
          <label for="payment_method" class="form-label">Method</label>
          <select name="payment_method"
                  id="payment_method"
                  class="form-select form-control">
            <option value="">Choose…</option>
            <option value="cash" {{ old('payment_method','cash')=='cash' ? 'selected':'' }}>
              Cash
            </option>
            <option value="card" {{ old('payment_method','cash')=='card' ? 'selected':'' }}>
              Card
            </option>
            <option value="bank_transfer" {{ old('payment_method','cash')=='bank_transfer' ? 'selected':'' }}>
              Bank Transfer
            </option>
          </select>
        </div>
        <div class="col-md-4">
          <label for="transaction_reference" class="form-label">Reference</label>
          <input type="text"
                 name="transaction_reference"
                 id="transaction_reference"
                 class="form-control"
                 value="{{ old('transaction_reference') }}">
        </div>
      </div>

      <div class="mb-4">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes"
                  id="notes"
                  class="form-control"
                  rows="2">{{ old('notes') }}</textarea>
      </div>

      <button type="submit" class="btn btn-primary" id="save_btn" disabled>
        Save Payments
      </button>
    </div>
  </form>
</div>
@endsection

@section('js')
<script>
  // Initialize Select2 in AJAX mode
  $('#student_select').select2({
    placeholder: $('#student_select').data('placeholder'),
    allowClear: true,
    width: '100%',
    ajax: {
      url: '{{ route("students.ajax") }}',
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term }),
      processResults: data => ({ results: data.results }),
      cache: true,
    },
    minimumInputLength: 1,
  });

  const duesSection = document.getElementById('dues_section');
  const duesBody    = document.getElementById('dues_body');
  const studentId   = document.getElementById('student_id');
  const totalPaidCell = document.getElementById('total_paid_cell');
  const remainingDueCell = document.getElementById('remaining_due_cell');

  // Autofill button
  document.getElementById('autofill_btn').addEventListener('click', function() {
    document.querySelectorAll('input[name^="allocations"]').forEach(input => {
      input.value = input.max;
    });
    updateTotals();
  });

  // Show spinner while loading dues
  async function loadDueItems(id) {
    document.getElementById('dues_spinner').style.display = '';
    studentId.value = id;
    if (!id) {
      duesSection.style.display = 'none';
      duesBody.innerHTML = '';
      document.getElementById('dues_summary_row').style.display = 'none';
      document.getElementById('dues_remaining_row').style.display = 'none';
      document.getElementById('dues_spinner').style.display = 'none';
      return;
    }

    try {
      const res = await fetch(`/students/${id}/due-items`);
      const items = await res.json();

      duesBody.innerHTML = '';
      let totalDue = 0;
      let totalPaid = 0;

      items.forEach(i => {
        const oldVal = '{{ json_encode(old("allocations", [])) }}';
        const prev  = JSON.parse(oldVal)[i.id] ?? '';
        duesBody.innerHTML += `
          <tr>
            <td>${i.invoice_number}</td>
            <td>${i.description}</td>
            <td class="text-end">${i.due.toFixed(2)}</td>
            <td>
              <input type="number"
                     name="allocations[${i.id}]"
                     min="0" max="${i.due}"
                     step="0.01"
                     class="form-control text-end"
                     value="${prev}">
              <div class="invalid-feedback"></div>
            </td>
          </tr>`;
        totalDue += i.due;
      });

      document.getElementById('dues_summary_row').style.display = '';
      document.getElementById('dues_remaining_row').style.display = '';
      document.getElementById('total_paid_cell').textContent = totalPaid.toFixed(2);
      document.getElementById('remaining_due_cell').textContent = totalDue.toFixed(2);

      duesSection.style.display = '';
    } catch (err) {
      console.error('Failed to load due items', err);
    }
    document.getElementById('dues_spinner').style.display = 'none';
    updateTotals();
  }

  // Highlight current row and show instant feedback
  function updateTotals() {
    let totalPaid = 0;
    let totalDue = 0;
    let validPayment = false;
    let overpaid = false;

    document.querySelectorAll('input[name^="allocations"]').forEach(input => {
      const due = parseFloat(input.max);
      const paid = parseFloat(input.value) || 0;
      totalPaid += paid;
      totalDue += due - paid;

      // Highlight row on focus
      input.addEventListener('focus', function() {
        document.querySelectorAll('tr').forEach(r => r.classList.remove('table-active'));
        input.closest('tr').classList.add('table-active');
      });

      // Remove previous color classes
      const row = input.closest('tr');
      row.classList.remove('table-success', 'table-warning', 'table-danger');

      // Instant feedback for invalid input
      const feedback = input.nextElementSibling;
      feedback.textContent = '';
      input.classList.remove('is-invalid');

      if (paid > due ) {
        row.classList.add('table-danger');
        feedback.textContent = 'Amount exceeds due!';
        input.classList.add('is-invalid');
        overpaid = true;
      } else if (paid < 0) {
        row.classList.add('table-danger');
        feedback.textContent = 'Amount can not be negative!';
        input.classList.add('is-invalid');
      }else if (paid === due && paid > 0) {
        row.classList.add('table-success');
        validPayment = true;
      } else if (paid > 0 && paid < due) {
        row.classList.add('table-warning');
        validPayment = true;
      } else if (paid < 0) {
        feedback.textContent = 'Amount cannot be negative!';
        input.classList.add('is-invalid');
        overpaid = true;
      }
    });

    totalPaidCell.innerText = totalPaid.toFixed(2);
    remainingDueCell.innerText = totalDue.toFixed(2);

    // Disable submit if no valid payment or any overpaid
    document.getElementById('save_btn').disabled = !validPayment || overpaid;
  }

  // Keyboard navigation: Enter/Tab moves to next input
  document.addEventListener('keydown', function(e) {
    if ((e.key === 'Enter' || e.key === 'Tab') && e.target.matches('input[name^="allocations"]')) {
      e.preventDefault();
      const inputs = Array.from(document.querySelectorAll('input[name^="allocations"]'));
      const idx = inputs.indexOf(e.target);
      if (idx > -1 && idx < inputs.length - 1) {
        inputs[idx + 1].focus();
      }
    }
  });

  // hook select2 change → load dues
  $('#student_select').on('change', function() {
    loadDueItems(this.value);
  });

  // on validation‐fail reload the old student’s dues
  @if(old('student_id'))
  document.addEventListener('DOMContentLoaded', () => {
    loadDueItems('{{ old("student_id") }}');
  });
  @endif

  // Update totals when typing in any allocations input
  document.addEventListener('input', function(e) {
    if (e.target.matches('input[name^="allocations"]')) {
      updateTotals();
    }
  });
</script>
@endsection
