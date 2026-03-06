@extends('layouts.app')
@section('plugins.Select2', true)
@section('title', 'Attendance Report')
@section('content_header_title', 'Attendance Report')
@section('content_header_subtitle', 'By Date Range, Class & Section')

@section('content_body')
<div class="card">
  <div class="card-body">

    {{-- Filters + Actions --}}
    <form method="GET" action="{{ route('attendance.report') }}" class="form-inline mb-3">
      <div class="form-group mr-2">
        <label for="start_date" class="mr-1">From</label>
        <input type="date" name="start_date" id="start_date"
               class="form-control" value="{{ $startDate }}"
               max="{{ now()->format('Y-m-d') }}" required>
      </div>
      <div class="form-group mr-2">
        <label for="end_date" class="mr-1">To</label>
        <input type="date" name="end_date" id="end_date"
               class="form-control" value="{{ $endDate }}"
               max="{{ now()->format('Y-m-d') }}" required>
      </div>
      <div class="form-group mr-2">
        <label for="class_id" class="mr-1">Class</label>
        <select name="class_id" id="class_id" class="form-control select2" style="width:120px">
          <option value="">All</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>
              {{ $c->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="form-group mr-2">
        <label for="section_id" class="mr-1">Section</label>
        <select name="section_id" id="section_id" class="form-control select2" style="width:120px" {{ request('class_id')?'':'disabled' }}>
          <option value="">All</option>
          @foreach($sections as $s)
            <option value="{{ $s->id }}" {{ request('section_id')==$s->id?'selected':'' }}>
              {{ $s->section_name }} ({{ $s->schoolClass->name }})
            </option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="btn btn-primary mr-2">View</button>
      <a href="{{ route('attendance.report') }}" class="btn btn-secondary mr-2">Reset</a>
      <a href="{{ route('attendance.report_pdf', request()->all()) }}"
         class="btn btn-outline-success">
        Download PDF
      </a>
    </form>

    @if($datesByMonth->isEmpty())
      <p class="text-center text-muted">No attendance records found.</p>
    @else
      @foreach($datesByMonth as $monthLabel => $dummy)
        @php
          // parse the month-year label and get days in that month
          $month = \Carbon\Carbon::createFromFormat('M-Y', $monthLabel);
          $daysInMonth = $month->daysInMonth;
        @endphp

        <h5 class="mt-4">{{ $monthLabel }}</h5>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-4 text-center" style="white-space:nowrap">
            <thead class="thead-light">
              <tr>
                <th>Student</th>
                @for($d=1; $d<=$daysInMonth; $d++)
                  <th>{{ str_pad($d,2,'0',STR_PAD_LEFT) }}</th>
                @endfor
              </tr>
            </thead>
            <tbody>
              @foreach($students as $stu)
                <tr>
                  <td class="font-weight-bold">{{ $stu->name }} ({{ $stu->student_id }} )</td>
                  @for($d=1; $d<=$daysInMonth; $d++)
                    @php
                      $dateKey = $month->format('Y-m') . '-' . str_pad($d,2,'0',STR_PAD_LEFT);
                      $status = $records[$stu->id][$dateKey] ?? null;
                    @endphp
                    <td>
                      @if(is_null($status))
                        •
                      @elseif($status === 'Present')
                        <span class="text-dark">P</span>
                      @else
                        <span class="text-danger">A</span>
                      @endif
                    </td>
                  @endfor
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endforeach

      {{-- Summary --}}
      <h5 class="mt-5">Summary</h5>
      <div class="table-responsive">
        <table class="table table-sm table-bordered w-50">
          <thead class="thead-light">
            <tr>
              <th>Student</th>
              <th>Present</th>
              <th>Absent</th>
              <th>Total</th>
              <th>%</th>
            </tr>
          </thead>
          <tbody>
            @foreach($students as $stu)
              @php
                $stuRec   = $records->get($stu->id, []);
                $present  = collect($stuRec)->filter(fn($s)=>$s==='Present')->count();
                $total    = count($stuRec);
                $percent  = $total ? round($present/$total*100,2) : 0;
              @endphp
              <tr>
                <td style="text-align: left">{{ $stu->name }} ({{ $stu->student_id }} )</td>
                <td>{{ $present }}</td>
                <td>{{ $total - $present }}</td>
                <td>{{ $total }}</td>
                <td>{{ $percent }}%</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

  </div>
</div>
@stop

@section('js')
<script>
  const allSections = @json($sections);
  $('#class_id, #section_id').select2({ placeholder:'Select', allowClear:true });

  function populateSections(cid, sel=null){
    const opts = allSections.filter(s=>s.class_id==cid);
    const $sec = $('#section_id').empty().append(new Option('All','',false,false))
                                .prop('disabled',!cid);
    opts.forEach(s=> $sec.append(new Option(s.section_name,s.id,false,false)));
    if(sel) $sec.val(sel);
    $sec.trigger('change');
  }

  $('#class_id').on('change',()=>{
    populateSections($('#class_id').val());
  });

  $(function(){
    const initClass = '{{ request("class_id") }}';
    const initSection = '{{ request("section_id") }}';
    if(initClass){
      $('#class_id').val(initClass).trigger('change');
      if(initSection) populateSections(initClass, initSection);
    }
  });
</script>
@endsection
