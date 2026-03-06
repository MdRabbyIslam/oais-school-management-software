{{-- resources/views/pages/payments/printable.blade.php --}}

@extends('layouts.app')

@section('title', 'Payment Receipt')

{{-- Print‐only CSS --}}
@section('css')
<link rel="stylesheet" media="print" href="{{ asset('/css/print.css') }}" />
<style>
  @media print {
    @page {

    size: A4 landscape;

      margin: 10mm;
    }
    body {
      margin: 0;
      padding: 0;
      background: transparent;
    }
    .main-header,
    .main-sidebar,
    .main-footer,
    .no-print {
      display: none !important;
    }


      /* up to 4 rows: side-by-side */
      .receipt-pairs {
        display: flex;
        justify-content: space-evenly;
        background: transparent;
      }
      .receipt-copy {
        width: 47%;
        page-break-after: auto;
      }

  }

  /* on‐screen preview */
  .receipt-pairs {
    display: flex;
    justify-content: space-evenly;
  }

  .receipt-copy {
    width: 47%;
    position: relative;
    border: 1px solid #000;
    padding: 10px;
    box-sizing: border-box;
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
  }


    .receipt-copy {
        width: 47%;
        }

  .receipt-copy::after {
    content: "";
    position: absolute;
    top: calc(50% + 50px);
    left: 50%;
    width: 300px;
    height: 300px;
    background: url('{{ asset("upload/images/Logo__Oysis.png") }}') no-repeat center center;
    background-size: contain;
    opacity: 0.15;
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
    top: -10px;
    max-height: 80px;
  }
  .school-name {
    font-size: 20px;
    line-height: 10px;
    font-weight: bold;
  }
   .school-name p:first-child {
    font-size: 21px;

  }
  .school-address {
    font-size: 14px;
    margin-top: -7px;
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
    border-top: 1px solid #000;
  }

  .items-table th,
  .items-table td {
    border: 1px solid #000;
    padding: 4px;
  }
  .items-table th {
    /* background: #3498db; */
    color: #000;
    text-align: left;
    font-weight: 700;
  }

  .items-table td {
    color: #000;
    font-weight: 500;
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
    font-size: 10px;

  }


@media print {
  /* 1) Allow background‐images but strip all background‐colors */
  html, body {
    -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
  }

  /* 2) Kill every background‐color on the page */
  * {
    background: none !important;
  }

  /* 3) Now restore *only* your watermark graphic */
  .receipt-copy::after {
    background: url('{{ asset("upload/images/Logo__Oysis.png") }}') no-repeat center center !important;
    background-size: contain !important;
    opacity: 0.15 !important;
  }
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
