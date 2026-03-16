<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Class Results</title>
    <style>
        @page { size: A3 landscape; margin: 8mm; }
        body { font-family: "Times New Roman", serif; color: #111; margin: 0; }
        .wrap { padding: 10px; }
        .actions { margin-bottom: 10px; }
        .actions button { padding: 8px 14px; }
        .header { text-align: center; margin-bottom: 12px; }
        .school { font-size: 28px; font-weight: 700; }
        .exam { font-size: 18px; font-weight: 700; }
        .class { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #222; padding: 4px 3px; text-align: center; vertical-align: middle; font-size: 14px; }
        thead th { background: #f2f2f2; font-weight: 700; }
        .left { text-align: left; padding-left: 5px; white-space: normal; word-break: break-word; line-height: 1.2; }
        .name-col { width: 180px; font-weight: 700; }
        .sl-col { width: 30px; }
        .small-col { width: 34px; }
        .final-col { width: 25px; font-weight: 700; }
        .vertical-heading {
            writing-mode: vertical-lr;
            text-orientation: mixed;
            white-space: nowrap;
            font-size: 16px;
            line-height: 1;
            padding: 8px 2px;
        }
        .group-heading {
            font-size: 16px;
            line-height: 1;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            overflow: hidden;
        }
        .name-heading { font-size: 17px; }
        .name-cell { font-size: 16px; font-weight: 700; }
        .tiny { font-size: 13px; }
        @media print {
            .actions { display: none; }
            .wrap { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="wrap">
        <div class="actions">
            <button type="button" onclick="window.print()">Print</button>
        </div>

        <div class="header">
            <div class="school">OASIS MODEL SCHOOL</div>
            <div class="exam">{{ $examAssessmentClass->examAssessment->name }}</div>
            <div class="class">Class: {{ $examAssessmentClass->schoolClass->name }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="sl-col" rowspan="2">SL</th>
                    <th class="name-col name-heading" rowspan="2">Students Name</th>
                    @foreach($subjectLayouts as $subjectLayout)
                        @php($subjectColumnCount = max(1, count($subjectLayout['component_columns'])) + ($subjectLayout['show_total_column'] ? 1 : 0) + (($subjectLayout['show_average_column'] ?? false) ? 1 : 0) + 1)
                        <th class="group-heading" colspan="{{ $subjectColumnCount }}">
                            {{ $subjectLayout['subject_name'] }} {{ rtrim(rtrim((string) $subjectLayout['total_marks'], '0'), '.') }}
                        </th>
                    @endforeach
                    <th class="final-col vertical-heading" rowspan="2">H. Work</th>
                    <th class="final-col vertical-heading" rowspan="2">Atten.</th>
                    <th class="final-col vertical-heading" rowspan="2">Total</th>
                    <th class="final-col vertical-heading" rowspan="2">GPA</th>
                    <th class="final-col vertical-heading" rowspan="2">Position</th>
                </tr>
                <tr>
                    @foreach($subjectLayouts as $subjectLayout)
                        @if(count($subjectLayout['component_columns']) > 0)
                            @foreach($subjectLayout['component_columns'] as $componentColumn)
                                <th class="small-col tiny">{{ $componentColumn['label'] }}</th>
                            @endforeach
                        @else
                            <th class="small-col tiny">W</th>
                        @endif
                        @if($subjectLayout['show_total_column'])
                            <th class="small-col tiny">T</th>
                        @endif
                        @if($subjectLayout['show_average_column'] ?? false)
                            <th class="small-col tiny">AV</th>
                        @endif
                        <th class="small-col tiny">G.P</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="left name-cell">{{ $row['student_name'] }}</td>
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
                        <td><strong>{{ rtrim(rtrim(number_format((float) ($row['homework_marks'] ?? 0), 2, '.', ''), '0'), '.') }}</strong></td>
                        <td><strong>{{ rtrim(rtrim(number_format((float) ($row['attendance_marks'] ?? 0), 2, '.', ''), '0'), '.') }}</strong></td>
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
    </div>
</body>
</html>
