<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Sheet</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { text-align: center; margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #f1f1f1; }
        .meta td { border: none; padding: 2px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Exam Result</div>
        <div>{{ $examAssessmentClass->examAssessment->name }} - {{ $examAssessmentClass->schoolClass->name }}</div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Student:</strong> {{ $studentEnrollment->student->name ?? 'Student #' . $studentEnrollment->student_id }}</td>
            <td><strong>Roll:</strong> {{ $studentEnrollment->roll_number ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong> {{ $result->total_obtained }}/{{ $result->total_marks }}</td>
            <td><strong>Percentage:</strong> {{ $result->percentage }}%</td>
        </tr>
        <tr>
            <td><strong>GPA:</strong> {{ $result->gpa }}</td>
            <td><strong>Grade:</strong> {{ $result->final_grade }} ({{ $result->is_pass ? 'PASS' : 'FAIL' }})</td>
        </tr>
        <tr>
            <td><strong>Position:</strong> {{ $result->effective_position ?? ($result->position ?? '-') }}</td>
            <td>
                @if($result->manual_position !== null)
                    <strong>Rank Source:</strong> Manual Override
                @endif
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Subject</th>
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
                    <td>{{ $subject['subject'] }}</td>
                    <td>{{ $subject['total_marks'] }}</td>
                    <td>{{ $subject['pass_marks'] }}</td>
                    <td>{{ $subject['is_absent'] ? 'ABSENT' : (($subject['term_obtained_marks'] ?? '-') ) }}</td>
                    <td>{{ number_format((float) ($subject['class_test_average'] ?? 0), 2) }}</td>
                    <td>{{ $subject['is_absent'] ? '-' : ($subject['obtained_marks'] ?? '-') }}</td>
                    <td>
                        {{ $subject['is_pass'] ? 'PASS' : 'FAIL' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
