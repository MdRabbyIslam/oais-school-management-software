<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Result</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: "Times New Roman", serif; color: #111; margin: 0; }
        .wrap { padding: 12px; }
        .header { text-align: center; margin-bottom: 16px; }
        .school { font-size: 28px; font-weight: 700; }
        .exam { font-size: 18px; font-weight: 700; }
        .class { font-size: 16px; font-weight: 700; }
        .meta { display: table; width: 100%; margin-bottom: 16px; }
        .meta-row { display: table-row; }
        .meta-cell { display: table-cell; padding: 4px 0; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #222; padding: 6px; font-size: 14px; text-align: center; }
        th { background: #f2f2f2; }
        .left { text-align: left; }
        .actions { margin-bottom: 12px; }
        .actions button { padding: 8px 14px; }
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

        <div class="meta">
            <div class="meta-row">
                <div class="meta-cell"><strong>Student:</strong> {{ $studentEnrollment->student->name ?? 'Student #' . $studentEnrollment->student_id }}</div>
                <div class="meta-cell"><strong>Roll:</strong> {{ $studentEnrollment->roll_number ?? '-' }}</div>
            </div>
            <div class="meta-row">
                <div class="meta-cell"><strong>Total:</strong> {{ $result->total_obtained }}/{{ $result->total_marks }}</div>
                <div class="meta-cell"><strong>Percentage:</strong> {{ $result->percentage }}%</div>
            </div>
            <div class="meta-row">
                <div class="meta-cell"><strong>GPA:</strong> {{ $result->gpa }}</div>
                <div class="meta-cell"><strong>Grade:</strong> {{ $result->final_grade }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="left">Subject</th>
                    <th>Total</th>
                    <th>Pass</th>
                    <th>Term</th>
                    <th>AV</th>
                    <th>Final</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjectRows as $subject)
                    <tr>
                        <td class="left">{{ $subject['subject'] }}</td>
                        <td>{{ $subject['total_marks'] }}</td>
                        <td>{{ $subject['pass_marks'] }}</td>
                        <td>{{ $subject['is_absent'] ? 'ABSENT' : (($subject['term_obtained_marks'] ?? '-') ) }}</td>
                        <td>{{ number_format((float) ($subject['class_test_average'] ?? 0), 2) }}</td>
                        <td>{{ $subject['is_absent'] ? '-' : ($subject['obtained_marks'] ?? '-') }}</td>
                        <td>{{ $subject['is_pass'] ? 'PASS' : 'FAIL' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
