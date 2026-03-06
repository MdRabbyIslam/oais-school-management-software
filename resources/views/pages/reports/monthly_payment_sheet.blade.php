@extends('layouts.app')
@section('plugins.Select2', true)
@section('title', 'Monthly Payment Sheet')
@section('content_header_title', 'Monthly Payment Sheet')
@section('content_header_subtitle', 'By Month, Class & Section')



@section('content_body')
<div class="card">
  <div class="card-body">



    @if ($errors->any())
      <div class="mb-1 p-0">
        <ul class="mb-0 p-0" style="list-style: none">
        @foreach ($errors->all() as $error)
            <li>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ $error }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </li>
        @endforeach
    </ul>
      </div>
  @endif


    {{-- Filters + Actions --}}
    <form method="POST" action="{{ route('reports.monthly_payment_sheet') }}" class="form-inline mb-3">
        @csrf

      <div class="form-group mr-2">
        <label for="month" class="mr-1">Month</label>
        <select name="month" id="month" class="form-control">
          @foreach(range(1,12) as $m)
            <option value="{{ $m }}" {{ (int)request('month', $month) === $m ? 'selected' : '' }}>
              {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group mr-2">
        <label for="year" class="mr-1">Year</label>
        <select name="year" id="year" class="form-control" style="width:100px">
          @php $yNow = now()->year; @endphp
          @foreach(range($yNow-3, $yNow+1) as $y)
            <option value="{{ $y }}" {{ (int)request('year', $year) === $y ? 'selected' : '' }}>
              {{ $y }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group mr-2">
        <label for="class_id" class="mr-1">Class</label>
        <select name="class_id" id="class_id" class="form-control select2" style="width:160px">
          <option value="">All</option>
          @isset($classes)
            @foreach($classes as $c)
              <option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>
                {{ $c->name }}
              </option>
            @endforeach
          @endisset
        </select>
      </div>

      <div class="form-group mr-2">
        <label for="section_id" class="mr-1">Section</label>
        <select name="section_id" id="section_id" class="form-control select2" style="width:160px" {{ request('class_id')?'':'disabled' }}>
          <option value="">All</option>
          @isset($sections)
            @foreach($sections as $s)
              <option value="{{ $s->id }}" {{ request('section_id')==$s->id?'selected':'' }}>
                {{ $s->section_name }} ({{ $s->schoolClass->name }})
              </option>
            @endforeach
          @endisset
        </select>
      </div>

      <div class="form-group mr-2">
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

      <button type="submit" class="btn btn-primary mr-2">View</button>
      <a href="{{ route('reports.show_monthly_payment_sheet') }}" class="btn btn-secondary mr-2">Reset</a>
        @if(isset($rows) && !empty($rows))
        <a href="#" id="printBtn" class="btn btn-outline-info" target="_blank">
            <i class="fas fa-print mr-1"></i> Print
        </a>
        @endif
    </form>

    @if (isset($rows))


        @if( empty($rows))
        <p class="text-center text-muted">No dues found for {{ \Carbon\Carbon::create($year,$month,1)->format('F Y') }}.</p>
        @else
        @php
            // Precompute column totals
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
            <table class="table table-sm table-bordered text-center" style="white-space:nowrap">
            <thead class="thead-light">
                <tr>
                <th style="min-width:60px">SL</th>
                <th style="min-width:240px; text-align:left">Student</th>
                @foreach($fees as $fee)
                    <th>{{ $fee->fee_name }}</th>
                @endforeach
                <th style="min-width:110px">Total</th>
                </tr>
                <tr>
                <th></th>
                <th class="text-left text-muted">Format: previous + current</th>
                @foreach($fees as $fee)
                    <th class="text-muted">prev + curr</th>
                @endforeach
                <th class="text-muted">৳</th>
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
                        <div class="text-muted small">(= {{ number_format($sum,2) }})</div>
                    </td>
                    @endforeach

                    <td><strong>{{ number_format($row['total'],2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-weight-bold">
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
    @endif
  </div>
</div>
@stop

@section('js')
<script>
  @if(isset($sections))
    const allSections = @json($sections);
  @else
    const allSections = [];
  @endif

  $('#class_id, #section_id, #student_id').select2({ placeholder:'Select', allowClear:true });

  function populateSections(cid, sel=null){
    const opts = allSections.filter(s=> String(s.class_id) === String(cid));
    const $sec = $('#section_id')
      .empty()
      .append(new Option('All','',false,false))
      .prop('disabled', !cid);
    opts.forEach(s=> $sec.append(new Option(`${s.section_name} (${s.school_class?.name ?? s.schoolClass?.name ?? ''})`, s.id, false, false)));
    if(sel) $sec.val(sel);
    $sec.trigger('change');
  }

  $('#class_id').on('change',()=>{
    populateSections($('#class_id').val());
  });

  $(function(){
    const initClass   = '{{ request("class_id") }}';
    const initSection = '{{ request("section_id") }}';
    if(initClass){
      $('#class_id').val(initClass).trigger('change');
      if(initSection) populateSections(initClass, initSection);
    }
  });


</script>

<script>
  // Initialize Select2 (unchanged)
  $('#student_select').select2({
    placeholder: $('#student_select').data('placeholder'),
    allowClear: true,
    width: '100%',
    ajax: {
      url: '{{ route("students.ajax") }}',
      dataType: 'json',
      delay: 250,
      data: params => ({
        q: params.term,
        id: null,               // normal search has no id
      }),
      processResults: data => ({ results: data.results }),
      cache: true,
    },
    minimumInputLength: 1,
  });

  // Preselect if student_id is in the URL
  (function preselectStudent() {
    const selectedId = '{{ request("student_id") }}';
    if (!selectedId) return;

    $.ajax({
      url: '{{ route("students.ajax") }}',
      dataType: 'json',
      data: { id: selectedId },     // <<< ask server for a single option
    }).then(function (data) {
      // Expecting: { id: <id>, text: "<Student ID> – <Name> (Class – Section)" }
      const item = data.result || data;           // support either {result:{...}} or flat
      if (!item || !item.id) return;

      const option = new Option(item.text, item.id, true, true);
      $('#student_select').append(option).trigger('change'); // selects it
    });
  })();
</script>
<script>
    $('#printBtn').on('click', function (e) {
    e.preventDefault();

    // Base print route (no params)
    let baseUrl = @json(route('reports.monthly_payment_sheet_print'));

    // Grab current form values
    let params = {
        month: $('#month').val(),
        year: $('#year').val(),
        class_id: $('#class_id').val(),
        section_id: $('#section_id').val(),
        student_id: $('#student_select').val(),
    };

    // Build query string from non-empty params
    let query = Object.keys(params)
        .filter(k => params[k] !== null && params[k] !== '')
        .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
        .join('&');

    // Open in new tab
    let fullUrl = baseUrl + (query ? '?' + query : '');
    window.open(fullUrl, '_blank');
});

</script>
@endsection
