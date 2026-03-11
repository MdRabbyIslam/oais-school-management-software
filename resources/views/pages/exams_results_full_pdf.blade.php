<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Result Sheet</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header { text-align: center; margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background: #f1f1f1; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Class Result Sheet</div>
        <div>{{ $examAssessmentClass->examAssessment->name }} - {{ $examAssessmentClass->schoolClass->name }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Roll</th>
                <th>Student</th>
                <th>Total</th>
                <th>Percentage</th>
                <th>GPA</th>
                <th>Grade</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $row)
                <tr>
                    <td>{{ $row->position ?? '-' }}</td>
                    <td>{{ $row->studentEnrollment->roll_number ?? '-' }}</td>
                    <td>{{ $row->studentEnrollment->student->name ?? 'Student #' . $row->student_enrollment_id }}</td>
                    <td>{{ $row->total_obtained }}/{{ $row->total_marks }}</td>
                    <td>{{ $row->percentage }}%</td>
                    <td>{{ $row->gpa }}</td>
                    <td>{{ $row->final_grade }}</td>
                    <td>{{ $row->is_pass ? 'PASS' : 'FAIL' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">No results found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
