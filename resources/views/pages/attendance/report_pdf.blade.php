<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Attendance Report {{ $startDate }}–{{ $endDate }}</title>
  <style>
    /*** PRINT CSS HERE ***/
    @page { margin: 20mm; }
    body { font-family: sans-serif; font-size: 12px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #333; padding: 4px; text-align: center; }
    th { background: #eee; }
    .student-cell { text-align: left; font-weight: bold; }
    .present { color: #000; }
    .absent  { color: red; }
    .empty   { color: #666; }
  </style>
</head>
<body>

  <h2 style="text-align:center;">
    Attendance Report<br>
    {{ request('class_id') ? App\Models\SchoolClass::find(request('class_id'))->name : 'All Classes' }}
    /
    {{ request('section_id') ? App\Models\Section::find(request('section_id'))->section_name : 'All Sections' }}<br>
    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
    –
    {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
  </h2>

  @foreach($datesByMonth as $monthLabel => $dummy)
    @php
      $month = \Carbon\Carbon::createFromFormat('M-Y', $monthLabel);
      $daysInMonth = $month->daysInMonth;
    @endphp

    <h4>{{ $monthLabel }}</h4>
    <table>
      <thead>
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
            <td class="student-cell">{{ $stu->name }} ({{ $stu->student_id }} )</td>
            @for($d=1; $d<=$daysInMonth; $d++)
              @php
                $dateKey = $month->format('Y-m') . '-' . str_pad($d,2,'0',STR_PAD_LEFT);
                $status = $records[$stu->id][$dateKey] ?? null;
              @endphp
              <td class="{{ $status === 'Present' ? 'present' : ($status==='Absent' ? 'absent' : 'empty') }}">
                {{ $status === 'Present' ? 'P' : ($status === 'Absent' ? 'A' : '•') }}
              </td>
            @endfor
          </tr>
        @endforeach
      </tbody>
    </table>
    <br>
  @endforeach

  {{-- Optional summary at end --}}
  <h4>Summary</h4>
  <table style="width:50%; margin-top:10px;">
    <thead>
      <tr>
        <th>Student</th><th>Present</th><th>Absent</th><th>Total</th><th>%</th>
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
        <td class="student-cell">{{ $stu->name }} ({{ $stu->student_id }} )</td>
          <td>{{ $present }}</td>
          <td>{{ $total - $present }}</td>
          <td>{{ $total }}</td>
          <td>{{ $percent }}%</td>
        </tr>
      @endforeach
    </tbody>
  </table>

</body>
</html>
