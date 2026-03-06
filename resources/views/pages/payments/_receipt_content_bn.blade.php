{{-- বাংলা রশিদ কন্টেন্ট --}}
@php
    // invoice নং গুলো একসঙ্গে দেখানোর জন্য
    $invoiceNos = $payment->allocations
        ->pluck('invoiceItem.invoice.invoice_number')
        ->unique()
        ->implode(', ');

    $cutoff = $payment->created_at;

    // সকল ফি আইটেম ধারাবাহিকভাবে সংগ্রহ এবং হিসাব
    $rows = $student->invoices->flatMap(function($invoice) use ($payment, $cutoff) {
        return $invoice->items->map(function($item) use ($invoice, $payment, $cutoff) {
            $prevPaid = $item->paymentAllocations
                ->filter(fn($pa) =>
                    $pa->payment->created_at->lt($cutoff) &&
                    $pa->payment_id !== $payment->id
                )
                ->sum('amount');

            $paidToday = optional(
                $payment->allocations
                    ->firstWhere('invoice_item_id', $item->id)
            )->amount ?: 0;

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
    ->filter(fn($r) => $r->paidToday > 0 || $r->due > 0);

    $totalPaid = $payment->amount;
    $totalDue  = $rows->sum(fn($r) => $r->due);
@endphp

<div class="receipt-content">

  {{-- শিরোনাম --}}
  <div class="receipt-header">
    <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="Logo">
    <div class="school-name">ওয়েসিস মডেল স্কুল</div>
    <div class="school-address">হোসেনপুর (হাজির দিঘি), সদর, দিনাজপুর</div>

  </div>

  <div class="receipt-title">পেমেন্ট রশিদ</div>

  {{-- তথ্য টেবিল --}}
  <table class="info-table">
    <tr>
      <td class="label">রশিদ নং</td>
      <td class="value">{{ $payment->receipt_number }}</td>
      <td class="label-right">তারিখ</td>
      <td class="value-right">{{ $payment->payment_date->format('d M Y') }}</td>
    </tr>
    <tr>
      <td class="label">শিক্ষার্থী আইডি</td>
      <td class="value">{{ $student->student_id }}</td>
      <td class="label-right">ইনভয়েস নং</td>
      <td class="value-right">{{ $invoiceNos }}</td>
    </tr>
    <tr>
      <td class="label">শিক্ষার্থী</td>
      <td class="value">{{ $student->name }}</td>
      <td class="label-right">ক্লাস/শাখা</td>
      <td class="value-right">
        {{ $student->section->schoolClass->name }}
        – {{ $student->section->section_name }}
      </td>
    </tr>
  </table>

  {{-- ফি সারাংশ --}}
  <table style="margin-top:1rem; " class="items-table">
    <thead>
      <tr>
        <th style="width:12%">ইনভ#</th>
        <th>বিবরণ</th>
        <th class="text-end" style="width:12%">ফি পরিমাণ</th>
        <th class="text-end" style="width:12%">পূর্বে পরিশোধ</th>
        <th class="text-end" style="width:12%">আজ পরিশোধিত</th>
        <th class="text-end" style="width:12%">বাকি</th>
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

  {{-- মোট --}}
  <table class="totals-table">
    <tr>
      <td class="label">মোট পরিশোধিত</td>
      <td class="text-end">{{ number_format($totalPaid, 2) }}</td>
    </tr>
    <tr>
      <td class="label">মোট বাকি</td>
      <td class="text-end">{{ number_format($totalDue, 2) }}</td>
    </tr>
  </table>

  {{-- মন্তব্য --}}
  @if($payment->notes)
    <p><strong>মন্তব্য:</strong> {{ $payment->notes }}</p>
  @endif

  {{-- স্বাক্ষর --}}
  <div style="margin-top:40px; display:flex; justify-content:space-between; page-break-inside:avoid;">
    <div style="text-align:center">
      <div style="border-top:1px solid #000; width:150px; margin:0 auto;"></div>
      <small>প্রদানকারীর স্বাক্ষর</small>
    </div>
    <div style="text-align:center">
      <div style="border-top:1px solid #000; width:150px; margin:0 auto;"></div>
      <small>গ্রহণকারীর স্বাক্ষর</small>
    </div>
  </div>

  {{-- ফুটার --}}
  <div class="receipt-footer">
    রেকর্ড করেছেন {{ $payment->recorder->name }}
    {{ $payment->created_at->format('d M Y H:i') }}<br>
  </div>
</div>
