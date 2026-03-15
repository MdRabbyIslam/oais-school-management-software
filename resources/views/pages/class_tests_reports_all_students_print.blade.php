<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Test Report - All Students</title>
    <style>
        body { font-family: "Times New Roman", serif; margin: 70px 14px 14px; color: #111827; background: #f8fafc; }
        .print-actions { position: fixed; top: 10px; right: 12px; z-index: 1000; }
        .print-actions button { border: 1px solid #111827; background: #ffffff; color: #111827; font-size: 13px; font-weight: 700; padding: 6px 10px; margin-left: 6px; cursor: pointer; }
        .sheet { background: #ffffff; border: 1px solid #d1d5db; padding: 10px 12px 12px; }
        .title { text-align: center; margin-bottom: 8px; }
        .title h1 { margin: 0; font-size: 34px; letter-spacing: 0.5px; }
        .title h2 { margin: 4px 0 0; font-size: 24px; }
        .meta { margin: 12px 0; font-size: 15px; display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: 6px 14px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #111; padding: 5px 4px; font-size: 13px; text-align: center; word-wrap: break-word; }
        th { background: #f3f4f6; }
        td.name { text-align: left; font-weight: 700; }
        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            body { margin: 0; background: #ffffff; }
            .print-actions { display: none; }
            .sheet { border: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    <div class="sheet">
        <div class="title">
            <h1>OASIS MODEL SCHOOL</h1>
            <h2>Class Test Report (All Students)</h2>
        </div>

        <div class="meta">
            <div><strong>Academic Year:</strong> {{ $filterSummary['academic_year'] }}</div>
            <div><strong>Term:</strong> {{ $filterSummary['term'] }}</div>
            <div><strong>Class:</strong> {{ $filterSummary['class'] }}</div>
            <div><strong>Subject:</strong> {{ $filterSummary['subject'] }}</div>
            <div><strong>Total Tests:</strong> {{ $classTests->count() }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">SL</th>
                    <th style="width: 8%;">Roll</th>
                    <th style="width: 20%;">Student</th>
                    @foreach($classTests as $test)
                        <th>{{ $test->subject->name ?? 'Subject' }}<br>{{ $test->name }}</th>
                    @endforeach
                    <th style="width: 8%;">Average</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['roll_number'] ?? '-' }}</td>
                        <td class="name">{{ $row['student_name'] }}</td>
                        @foreach($row['marks'] as $marks)
                            <td>{{ $marks !== null ? number_format((float) $marks, 2) : '-' }}</td>
                        @endforeach
                        <td>{{ $row['average'] !== null ? number_format((float) $row['average'], 2) : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + $classTests->count() }}">No students found for selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
