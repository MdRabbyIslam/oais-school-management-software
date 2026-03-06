@extends('layouts.app')

@section('title', 'Monthly Payment Sheet — Print')

{{-- Print-only CSS --}}
@section('css')
<link rel="stylesheet" media="print" href="{{ asset('/css/print.css') }}" />
@php
  $rowsCount = is_countable($rows ?? []) ? count($rows) : 0;
  $feeCount  = is_countable($fees ?? []) ? count($fees) : 0;
@endphp
<style>
  @media print {
    @page {
      /* Heuristic: many columns or many rows -> landscape */
      @if($feeCount > 6 || $rowsCount > 20)
        size: legal landscape;
      @else
        size: legal portrait;
      @endif
      margin: 10mm;
    }
    body { margin:0; padding:0; }
    .main-header, .main-sidebar, .main-footer, .no-print { display:none !important; }
    .card, .card-body { border:0 !important; box-shadow:none !important; padding:0 !important; }
    table { page-break-inside:auto; }
    tr { page-break-inside:avoid; page-break-after:auto; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
  }

  .sheet-wrap { font-family: DejaVu Sans, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif; }
  .sheet-header {
    display:flex; align-items:center; justify-content:center; position:relative; margin-bottom:8px;
  }
  .sheet-header .logo { position:absolute; left:0; top:0; }
  .sheet-header img { max-height:70px; max-width:120px; }
  .sheet-header .title { text-align:center; width:100%; }
  .sheet-header .school-name { font-size:18px; font-weight:700; line-height:1.1; }
  .sheet-header .subtitle { color:#555; font-size:12px; }
  .sheet-header .report-title { font-size:14px; color:#2874a6; margin-top:4px; }

  .meta-line { display:flex; justify-content:space-between; font-size:12px; margin:6px 0 10px; color:#444; }
  .table-responsive { overflow-x:auto; }
  table.report-table { width:100%; border-collapse:collapse; font-size:12px; text-align:center; white-space:nowrap; }
  table.report-table th, table.report-table td { border:1px solid #000; padding:4px 6px; vertical-align:middle; }
  table.report-table thead th { background:#e9f3fb; color:#000; font-weight:700; }
  table.report-table tbody td { font-weight:600; color: #000; }
  table.report-table tfoot td { font-weight:700; background:#f7f7f7; }

  .cell-note { color:#666; font-size:11px; }
  .student-col { text-align:left; min-width:240px; }
  .sl-col { min-width:50px; }
  .total-col { min-width:100px; }

  /* Watermark (optional) — faint logo centered */
  .watermark {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    opacity: .04; z-index: -1;
    background-repeat:no-repeat; background-position:center center; background-size: 300px auto;
    width: 300px; height: 300px;
  }

</style>
@stop

@section('content')
<div class="sheet-wrap">

  {{-- On-screen actions --}}
  <div class="mb-2 mt-2 no-print">
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">Back</a>
    <button onclick="window.print()" class="btn btn-info btn-sm ml-2">
      <i class="icon-printer mr-1"></i> Print
    </button>
  </div>

  {{-- Watermark (optional) --}}
  @if(!empty($logoPath))
    <div class="watermark" style="background-image:url('{{ $logoPath }}')"></div>
  @endif

  {{-- Header --}}
  <div class="sheet-header">
    <div class="logo">
    <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="Logo">

    </div>
    <div class="title">
        <div class="school-name">
            <p>{{ config('app.school_name_bn', 'ওয়েসিস মডেল স্কুল') }}</p>

            <p>{{ config('app.school_name', 'Oasis Model School') }}</p>
        </div>
      <div class="subtitle">{{ $subtitle ?? '' }}</div>
      <div class="report-title">{{ $title ?? ('Monthly Payment Sheet – ' . \Carbon\Carbon::create($year, $month, 1)->format('F') . ' / ' . $year) }}</div>
      <div class="report-title">{{  ('Class – ' . $class.' ' .($section?" / $section":'')) }}</div>
    </div>
    <div style="width:120px"></div>
  </div>

  {{-- Meta --}}
  <div class="meta-line">
    <div></div>
    <div><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</div>
  </div>

  @if(empty($rows))
    <p class="text-center text-muted">No dues found for {{ \Carbon\Carbon::create($year,$month,1)->format('F Y') }}.</p>
  @else
    @php
      // compute column totals once
      $feeTotals = [];
      $grandTotal = 0.0;
      foreach ($rows as $r) {
          foreach ($fees as $fee) {
              $feeTotals[$fee->id] = ($feeTotals[$fee->id] ?? 0) + ($r['fees'][$fee->id] ?? 0);
          }
          $grandTotal += $r['total'];
      }
    @endphp

    <div class="table-responsive">
      <table class="report-table">
        <thead>
          <tr>
            <th class="sl-col">SL</th>
            <th class="student-col">Student</th>
            @foreach($fees as $fee)
              <th>{{ $fee->fee_name }}</th>
            @endforeach
            <th class="total-col">Total</th>
          </tr>
          <tr>
            <th></th>
            <th class="text-left cell-note">Format: previous + current</th>
            @foreach($fees as $fee)
              <th class="cell-note">prev + curr</th>
            @endforeach
            <th class="cell-note">৳</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $row)
            <tr>
              <td>{{ $row['sl'] }}</td>
              <td class="text-left">{{ $row['student'] }}</td>

              @foreach($fees as $fee)
                @php
                  $disp = $row['feesDisplay'][$fee->id] ?? '0.00 + 0.00';
                  $sum  = $row['fees'][$fee->id] ?? 0;
                @endphp
                <td>
                  {{ $disp }}
                  <div class="cell-note">(= {{ number_format($sum, 2) }})</div>
                </td>
              @endforeach

              <td><strong>{{ number_format($row['total'], 2) }}</strong></td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" class="text-right">Column Totals</td>
            @foreach($fees as $fee)
              <td>{{ number_format($feeTotals[$fee->id] ?? 0, 2) }}</td>
            @endforeach
            <td>{{ number_format($grandTotal, 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  @endif
</div>
@stop
