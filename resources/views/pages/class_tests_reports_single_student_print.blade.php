<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Test Report - Single Student</title>
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
        th, td { border: 1px solid #111; padding: 6px 5px; font-size: 14px; text-align: center; word-wrap: break-word; }
        th { background: #f3f4f6; }
        td.left { text-align: left; font-weight: 700; }
        td.fail { color: #b91c1c; font-weight: 700; }
        td.pass { color: #065f46; font-weight: 700; }
        td.absent { color: #92400e; font-weight: 700; }
        .summary { margin-top: 10px; font-size: 16px; font-weight: 700; text-align: right; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
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
            <h2>Class Test Report (Single Student)</h2>
        </div>

        <div class="meta">
            <div><strong>Academic Year:</strong> {{ $filterSummary['academic_year'] }}</div>
            <div><strong>Term:</strong> {{ $filterSummary['term'] }}</div>
            <div><strong>Class:</strong> {{ $filterSummary['class'] }}</div>
            <div><strong>Subject:</strong> {{ $filterSummary['subject'] }}</div>
            <div><strong>Student:</strong> {{ $studentEnrollment->student->name ?? '-' }}</div>
            <div><strong>Roll:</strong> {{ $studentEnrollment->roll_number ?? '-' }}</div>
            <div><strong>Total Tests:</strong> {{ $rows->count() }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 6%;">SL</th>
                    <th style="width: 18%;">Term</th>
                    <th style="width: 20%;">Subject</th>
                    <th style="width: 26%;">Class Test</th>
                    <th style="width: 10%;">Total</th>
                    <th style="width: 10%;">Obtained</th>
                    <th style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    @php($statusClass = $row['status'] === 'PASS' ? 'pass' : ($row['status'] === 'FAIL' ? 'fail' : ($row['status'] === 'ABSENT' ? 'absent' : '')))
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['term_name'] }}</td>
                        <td class="left">{{ $row['subject_name'] }}</td>
                        <td class="left">{{ $row['test_name'] }}</td>
                        <td>{{ number_format((float) $row['total_marks'], 2) }}</td>
                        <td>{{ $row['obtained'] !== null ? number_format((float) $row['obtained'], 2) : '-' }}</td>
                        <td class="{{ $statusClass }}">{{ $row['status'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No class tests found for selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            Average Marks: {{ $average !== null ? number_format((float) $average, 2) : '-' }}
        </div>
    </div>
</body>
</html>
