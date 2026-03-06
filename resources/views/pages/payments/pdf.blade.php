<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Receipt {{ $payment->receipt_number }}</title>
  <style>
    @page { margin: 2.5mm; }
    body { margin:0; padding:0; font-family: DejaVu Sans, sans-serif; font-size:12px; }
    table.outer {
      width:100%;
      table-layout: fixed;
      border-collapse: collapse;
    }
    table.outer td {
      vertical-align: top;
      padding: 5mm;
      width: 50%;
    }
    .copy-container {
      border:1px solid #ccc;
      padding:4mm;
      box-sizing: border-box;
    }
    .copy-label {
        display: block;
      text-align: center;
      font-weight:bold;

      font-size:5px;
    }



    .school-name   { font-size: 15px; font-weight: bold; color: #1a5276; margin-bottom: 2px; }
    .school-motto  { font-style: italic; color: #5d6d7e; font-size:7px; }
    .school-address{ font-size: 6px; margin-top: 2px; }


    /* … (rest of your inner styles unchanged) … */
  </style>
</head>
<body>
  <table class="outer">
    <tr>
      <!-- Office Copy -->
      <td>
        <div class="copy-container">
          <span class="copy-label">Office Copy</span>
          @include('pages.payments._pdf_content', ['payment' => $payment])
        </div>
      </td>

      <!-- Student Copy -->
      <td>
        <div class="copy-container">
          <span class="copy-label">Student Copy</span>
          @include('pages.payments._pdf_content', ['payment' => $payment])
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
