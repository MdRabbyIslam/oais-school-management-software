<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Result Sheet</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 8px; }
        .header { text-align: center; margin-bottom: 8px; }
        .school { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .exam { font-size: 16px; font-weight: 700; }
        .class { font-size: 14px; font-weight: 700; margin-top: 2px; }
        table { width: 100%;border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #222; padding: 2px 1px; text-align: center; vertical-align: middle; }
        thead th { background: #f3f3f3; font-weight: 700; }
        .left { text-align: left; padding-left: 4px; word-break: break-word; }
        .name-col { font-weight: 700; }
        .final-col { font-weight: 700; }
        .tiny { font-size: 7px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school">OASIS MODEL SCHOOL</div>
        <div class="exam">{{ $examAssessmentClass->examAssessment->name }}</div>
        <div class="class">Class: {{ $examAssessmentClass->schoolClass->name }}</div>
    </div>

    @php($slWidth = 16)
    @php($nameWidth = 180)
    @php($subjectCellWidth = 16)
    @php($finalWidth = 24)
    @php($tableWidth = $slWidth + $nameWidth + ($finalWidth * 3))
    @foreach($subjectLayouts as $subjectLayout)
        @php($subjectColumnCount = max(1, count($subjectLayout['component_columns'])) + ($subjectLayout['show_total_column'] ? 1 : 0) + (($subjectLayout['show_average_column'] ?? false) ? 1 : 0) + 1)
        @php($tableWidth += ($subjectColumnCount * $subjectCellWidth))
    @endforeach

    <table style="width: 100%">
        <colgroup>
            <col style="width: {{ $slWidth }}px;">
            <col style="width: 500px;">
            @foreach($subjectLayouts as $subjectLayout)
                @php($subjectColumnCount = max(1, count($subjectLayout['component_columns'])) + ($subjectLayout['show_total_column'] ? 1 : 0) + (($subjectLayout['show_average_column'] ?? false) ? 1 : 0) + 1)
                @for($i = 0; $i < $subjectColumnCount; $i++)
                    <col style="width: {{ $subjectCellWidth }}px;">
                @endfor
            @endforeach
            <col style="width: {{ $finalWidth }}px;">
            <col style="width: {{ $finalWidth }}px;">
            <col style="width: {{ $finalWidth }}px;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">SL</th>
                <th class="name-col" rowspan="2">Students Name</th>
                @foreach($subjectLayouts as $subjectLayout)
                    @php($subjectColumnCount = max(1, count($subjectLayout['component_columns'])) + ($subjectLayout['show_total_column'] ? 1 : 0) + (($subjectLayout['show_average_column'] ?? false) ? 1 : 0) + 1)
                    <th colspan="{{ $subjectColumnCount }}">
                        {{ $subjectLayout['subject_name'] }} {{ rtrim(rtrim((string) $subjectLayout['total_marks'], '0'), '.') }}
                    </th>
                @endforeach
                <th class="final-col" rowspan="2">Total</th>
                <th class="final-col" rowspan="2">GPA</th>
                <th class="final-col" rowspan="2">Position</th>
            </tr>
            <tr>
                @foreach($subjectLayouts as $subjectLayout)
                    @if(count($subjectLayout['component_columns']) > 0)
                        @foreach($subjectLayout['component_columns'] as $componentColumn)
                            <th class="tiny">{{ $componentColumn['label'] }}</th>
                        @endforeach
                    @else
                        <th class="tiny">W</th>
                    @endif

                    @if($subjectLayout['show_total_column'])
                        <th class="tiny">T</th>
                    @endif
                    @if($subjectLayout['show_average_column'] ?? false)
                        <th class="tiny">AV</th>
                    @endif
                    <th class="tiny">G.P</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="left">{{ $row['student_name'] }}</td>

                    @foreach($subjectLayouts as $subjectLayout)
                        @php($cell = $row['subject_data'][$subjectLayout['assessment_subject_id']] ?? ['components' => [], 'total' => null, 'gpa' => 0])
                        @if(count($subjectLayout['component_columns']) > 0)
                            @foreach($cell['components'] as $componentValue)
                                <td>{{ $componentValue === null ? '-' : rtrim(rtrim(number_format((float) $componentValue, 2, '.', ''), '0'), '.') }}</td>
                            @endforeach
                        @else
                            <td>{{ $cell['total'] === null ? '-' : rtrim(rtrim(number_format((float) $cell['total'], 2, '.', ''), '0'), '.') }}</td>
                        @endif

                        @if($subjectLayout['show_total_column'])
                            <td>{{ $cell['total'] === null ? '-' : rtrim(rtrim(number_format((float) $cell['total'], 2, '.', ''), '0'), '.') }}</td>
                        @endif
                        @if($subjectLayout['show_average_column'] ?? false)
                            <td>{{ rtrim(rtrim(number_format((float) ($cell['average'] ?? 0), 2, '.', ''), '0'), '.') }}</td>
                        @endif
                        <td>{{ rtrim(rtrim(number_format((float) $cell['gpa'], 2, '.', ''), '0'), '.') }}</td>
                    @endforeach

                    <td><strong>{{ rtrim(rtrim(number_format((float) $row['total'], 2, '.', ''), '0'), '.') }}</strong></td>
                    <td><strong>{{ rtrim(rtrim(number_format((float) $row['gpa'], 2, '.', ''), '0'), '.') }}</strong></td>
                    <td><strong>{{ $row['position'] }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="99">No results found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
