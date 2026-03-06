{{-- resources/views/pages/payments/_receipt_content.blade.php --}}

@php
    // 1) Unique invoice numbers for the header
    $invoiceNos = $payment->allocations
        ->pluck('invoiceItem.invoice.invoice_number')
        ->unique()
        ->implode(', ');

    // 2) Cutoff: only count allocations up to this payment
    $cutoff = $payment->created_at;

    // 3) Build a flat list of every invoice item for this student,
    //    computing original, prevPaid, paidToday and outstanding (due)
    $rows = $student->invoices->flatMap(function($invoice) use ($payment, $cutoff) {
        return $invoice->items->map(function($item) use ($invoice, $payment, $cutoff) {
            // amount paid strictly before this payment
            $prevPaid = $item->paymentAllocations
                ->filter(fn($pa) =>
                    $pa->payment->created_at->lt($cutoff) &&
                    $pa->payment_id !== $payment->id
                )
                ->sum('amount');

            // amount allocated in this payment
            $paidToday = optional(
                $payment->allocations
                    ->firstWhere('invoice_item_id', $item->id)
            )->amount ?: 0;

            // remaining balance
            $due = $item->amount - ($prevPaid + $paidToday);

            return (object)[
                'invoice'    => $invoice->invoice_number,
                'description'=> $item->description,
                'original'   => $item->amount,
                'prevPaid'   => $prevPaid,
                'paidToday'  => $paidToday,
                'due'        => $due,
            ];
        });
    })
    // only keep items that were paid today or still owe money
    ->filter(fn($r) => $r->paidToday > 0 || $r->due > 0);

    // 4) Totals
    $totalPaid = $payment->amount;
    $totalDue  = $rows->sum(fn($r) => $r->due);
@endphp

<div class="receipt-content">

  {{-- HEADER --}}
  <div class="receipt-header">
    {{-- logo at left (absolute) --}}
    <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="Logo">
    <div class="school-name">
      {{ config('app.school_name', 'Oasis Model School') }}
    </div>
    {{-- fixed address --}}
    <div class="school-address">
      Hossenpur (Hajir Dhighi), Sadar, Dinajpur
    </div>
  </div>

  {{-- TITLE --}}
  <div class="receipt-title">PAYMENT RECEIPT</div>

  {{-- PAYMENT & STUDENT INFO --}}
  <table class="info-table">
    <tr>
      <td class="label">Receipt #</td>
      <td class="value">{{ $payment->receipt_number }}</td>
      <td class="label-right">Date</td>
      <td class="value-right">{{ $payment->payment_date->format('d M Y') }}</td>
    </tr>
    <tr>
      <td class="label">Student ID</td>
      <td class="value">{{ $student->student_id }}</td>
      <td class="label-right">Invoice #</td>
      <td class="value-right">{{ $invoiceNos }}</td>
    </tr>
    <tr>
      <td class="label">Student</td>
      <td class="value">{{ $student->name }}</td>
      <td class="label-right">Class/Section</td>
      <td class="value-right">
        {{ $student->section->schoolClass->name }}
         – {{ $student->section->section_name }}
      </td>
    </tr>
  </table>

  {{-- FEES SUMMARY --}}
  <table class="items-table" style="margin-top:1rem; ">
    <thead>
      <tr>
        <th style="width:12%">Inv#</th>
        <th>Description</th>
        <th class="text-end" style="width:12%">Fee Amount</th>
        <th class="text-end" style="width:12%">Prev. Paid</th>
        <th class="text-end" style="width:12%">Paid Today</th>
        <th class="text-end" style="width:12%">Outstanding</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $r)
        <tr>
          <td>{{ $r->invoice }}</td>
          <td>{{ $r->description }}</td>
          <td class="text-end">{{ number_format($r->original, 2) }}</td>
          <td class="text-end">{{ number_format($r->prevPaid, 2) }}</td>
          <td class="text-end">{{ number_format($r->paidToday, 2) }}</td>
          <td class="text-end">{{ number_format($r->due, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- TOTALS --}}
  <table class="totals-table">
    <tr>
      <td class="label">Total Paid</td>
      <td class="text-end">{{ number_format($totalPaid, 2) }}</td>
    </tr>
    <tr>
      <td class="label">Total Due</td>
      <td class="text-end">{{ number_format($totalDue, 2) }}</td>
    </tr>
  </table>

  @if($payment->notes)
    <p><strong>Notes:</strong> {{ $payment->notes }}</p>
  @endif

  {{-- SIGNATURE AREA --}}
  <div style="margin-top:40px; display:flex; justify-content:space-between; page-break-inside:avoid;">
    <div style="text-align:center">
      <div style="border-top:1px solid #000; width:150px; margin:0 auto;"></div>
      <small>Payer Signature</small>
    </div>
    <div style="text-align:center">
      <div style="border-top:1px solid #000; width:150px; margin:0 auto;"></div>
      <small>Recipient’s Signature</small>
    </div>
  </div>

  {{-- FOOTER --}}
  <div class="receipt-footer">
    Recorded by {{ $payment->recorder->name }}
    on {{ $payment->created_at->format('d M Y H:i') }}<br>
  </div>

</div>
