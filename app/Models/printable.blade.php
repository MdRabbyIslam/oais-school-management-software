{{-- resources/views/pages/payments/printable.blade.php --}}

@extends('layouts.app')

@section('title', 'Payment Receipt')

{{-- Print‐only CSS --}}
@section('css')
<link rel="stylesheet" media="print" href="{{ asset('/css/print.css') }}" />
<style>
  @media print {
    @page {
      @if($rowsCount > 4)
        size: A4 portrait;
      @else
        size: A4 landscape;
      @endif
      margin: 10mm;
    }
    body {
      margin: 0;
      padding: 0;
    }
    .main-header,
    .main-sidebar,
    .main-footer,
    .no-print {
      display: none !important;
    }

    @if($rowsCount <= 4)
      /* up to 4 rows: side-by-side */
      .receipt-pairs {
        display: flex;
        justify-content: space-evenly;
        gap: 6%;

      }
      .receipt-copy {
        width: 45%;
        page-break-after: auto;
      }
    @else
        .receipt-pairs {
            display: flex;
            flex-direction: column;
            gap: 10%;
        }

      /* more than 4 rows: one per page */
      .receipt-copy {
        width: 100%;
        page-break-after: auto;
        margin-bottom: 20px;
      }
      .receipt-copy:last-child {
        page-break-after: auto;
      }
    @endif
  }

  /* on‐screen preview */
  .receipt-pairs {
    display: flex;
    justify-content: space-evenly;
    gap: 6%;
  }

  .receipt-copy {
    width: 45%;
    position: relative;
    border: 1px solid #333;
    padding: 10px;
    box-sizing: border-box;
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
  }

    @if($rowsCount > 4)
        .receipt-copy {
            width: 100%;
        }
        .receipt-pairs {
            gap: 200px;
        }
    @else
    .receipt-copy {
        width: 45%;
        }
    @endif

  .receipt-copy::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 200px;
    height: 200px;
    background: url('{{ asset("upload/images/Logo__Oysis.png") }}') no-repeat center center;
    background-size: contain;
    opacity: 0.05;
    transform: translate(-50%, -50%);
    pointer-events: none;
  }

  .receipt-header {
    position: relative;
    margin-bottom: 8px;
    text-align: center;
  }
  .receipt-header img {
    position: absolute;
    left: 0;
    top: 0;
    max-height: 60px;
  }
  .school-name {
    font-size: 16px;
    font-weight: bold;
  }
  .school-address {
    font-size: 10px;
    margin-top: 2px;
  }

  .receipt-title {
    text-align: center;
    font-size: 14px;
    color: #2874a6;
    margin: 8px 0;
  }

  .info-table,
  .items-table,
  .totals-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }

  .info-table td {
    padding: 4px 0;
    vertical-align: top;
  }
  .info-table .label {
    width: 25%;
    font-weight: bold;
    white-space: nowrap;
  }
  .info-table .value {
    width: 25%;
    padding-left: 6px;
  }
  .info-table .label-right {
    width: 25%;
    font-weight: bold;
    white-space: nowrap;
    padding-left: 16px;
  }
  .info-table .value-right {
    width: 25%;
    padding-left: 6px;
  }
  .info-table tr + tr td {
    border-top: 1px solid #ddd;
  }

  .items-table th,
  .items-table td {
    border: 1px solid #ccc;
    padding: 4px;
  }
  .items-table th {
    background: #3498db;
    color: #fff;
    text-align: left;
  }

  .totals-table td {
    padding: 4px;
  }
  .totals-table .label {
    font-weight: bold;
  }

  .receipt-footer {
    margin-top: auto;
    font-size: 10px;
    text-align: center;
    color: #666;
  }

  .copy-label {
    text-align: center;
    font-weight: bold;
    margin-bottom: 8px;
  }
</style>
@stop

@section('content')
  <div class="mb-2 mt-2 no-print">
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">Back</a>
    <button onclick="printInvoice()" type="button" class="btn btn-info btn-sm ml-3">
      <i class="icon-printer mr-2"></i> Print Receipt
    </button>
  </div>

  @if($rowsCount <= 4)
    {{-- up to 4 rows: two copies side-by-side --}}
    <div id="receipt-pairs" class="receipt-pairs">
      <div class="receipt-copy">
        <div class="copy-label">Office Copy</div>
        @include('pages.payments._receipt_content', compact('payment','student'))
      </div>
      <div class="receipt-copy">
        <div class="copy-label">Student Copy</div>
        @include('pages.payments._receipt_content', compact('payment','student'))
      </div>
    </div>
  @else
    {{-- more than 4 rows: one per page --}}
    <div class="receipt-copy">
      <div class="copy-label">Office Copy</div>
      @include('pages.payments._receipt_content', compact('payment','student'))
    </div>
    <div class="receipt-copy">
      <div class="copy-label">Student Copy</div>
      @include('pages.payments._receipt_content', compact('payment','student'))
    </div>
  @endif
@stop

@section('js')
<script src="{{ asset('js/print.js') }}"></script>
<script>
  function printInvoice() {
    window.print();
  }
  document.addEventListener('DOMContentLoaded', printInvoice);
</script>
@endsection
