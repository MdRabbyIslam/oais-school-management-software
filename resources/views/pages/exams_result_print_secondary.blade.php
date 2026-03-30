<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Print (Class 3+)</title>
    <style>
        @page { size: A4 portrait; margin: 7mm; }
        body { font-family: "Times New Roman", serif; color: #111; margin: 0; }
        .wrap { border: 1px solid #222; padding: 14px; box-sizing: border-box; }
        .actions { margin-bottom: 8px; }
        .actions button { padding: 6px 12px; }

        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px; }
        .logo-wrap { width: 100px; text-align: center; }
        .logo-wrap img { max-width: 88px; max-height: 88px; }
        .title { flex: 1; text-align: center; }
        .bismillah { font-size: 13px; margin-bottom: 2px; }
        .school-bn { font-size: 34px; font-weight: 700; line-height: 1.1; }
        .school-en { font-size: 50px; font-weight: 700; line-height: 1.05; margin-top: 2px; }
        .motto { font-size: 13px; margin-top: 4px; }
        .term-box {
            display: inline-block;
            border: 1px solid #222;
            padding: 3px 12px;
            margin-top: 8px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .grade-box { width: 210px; border-collapse: collapse; }
        .grade-box th, .grade-box td { border: 1px solid #222; padding: 3px 6px; font-size: 12px; text-align: center; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta td { border: 1px solid #222; padding: 5px 6px; font-size: 14px; }

        .scheme, .marks, .summary-table, .manual { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .scheme th, .scheme td,
        .marks th, .marks td,
        .summary-table th, .summary-table td,
        .manual th, .manual td { border: 1px solid #222; padding: 4px 5px; font-size: 13px; text-align: center; }

        .marks th { font-weight: 700; }
        .left { text-align: left !important; }

        .attendance-row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .attendance-row td { border: 1px solid #222; padding: 5px 6px; font-size: 14px; }
        .line { display: inline-block; border-bottom: 1px dotted #666; min-width: 90px; height: 16px; vertical-align: bottom; }

        .comment-box { width: 100%; border: 1px solid #222; min-height: 56px; margin-bottom: 8px; padding: 6px; font-size: 13px; }
        .sign-row { width: 100%; border-collapse: collapse; }
        .sign-row td { border: 1px solid #222; height: 42px; padding: 4px 6px; font-size: 13px; vertical-align: bottom; }

        @media print {
            .actions { display: none; }
            .wrap { padding: 12px; }
        }
    </style>
</head>
<body onload="window.print()">
<div class="wrap">
    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="top">
        <div class="logo-wrap">
            <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="School Logo">
        </div>
        <div class="title">
            <div class="bismillah">&#x09AC;&#x09BF;&#x09B8;&#x09AE;&#x09BF;&#x09B2;&#x09CD;&#x09B2;&#x09BE;&#x09B9;&#x09BF;&#x09B0; &#x09B0;&#x09BE;&#x09B9;&#x09AE;&#x09BE;&#x09A8;&#x09BF;&#x09B0; &#x09B0;&#x09BE;&#x09B9;&#x09BF;&#x09AE;</div>
            <div class="school-bn">&#x0993;&#x09DF;&#x09C7;&#x09B8;&#x09BF;&#x09B8; &#x09AE;&#x09A1;&#x09C7;&#x09B2; &#x09B8;&#x09CD;&#x0995;&#x09C1;&#x09B2;</div>
            <div class="school-en">Oasis Model School</div>
            <div class="motto">"&#x09AE;&#x09BE;&#x09A8;&#x09B8;&#x09AE;&#x09CD;&#x09AE;&#x09A4; &#x09B6;&#x09BF;&#x0995;&#x09CD;&#x09B7;&#x09BE; &#x09AA;&#x09BE;&#x0993;&#x09DF;&#x09BE;&#x09AA;&#x09BE;&#x09B6;&#x09BF; &#x0986;&#x09A6;&#x09B0;&#x09CD;&#x09B6; &#x09AE;&#x09BE;&#x09A8;&#x09C1;&#x09B7; &#x09B9;&#x09BF;&#x09B8;&#x09C7;&#x09AC;&#x09C7; &#x099B;&#x09BE;&#x09A4;&#x09CD;&#x09B0;-&#x099B;&#x09BE;&#x09A4;&#x09CD;&#x09B0;&#x09C0; &#x0997;&#x09DC;&#x09C7; &#x0986;&#x09AE;&#x09BE;&#x09A6;&#x09C7;&#x09B0; &#x09B2;&#x0995;&#x09CD;&#x09B7;&#x09CD;&#x09AF;"</div>
            <div class="term-box">{{ strtoupper($examAssessmentClass->examAssessment->name) }}</div>
        </div>
        <table class="grade-box">
            <thead>
                <tr><th>&#x09A8;&#x09AE;&#x09CD;&#x09AC;&#x09B0;</th><th>&#x0997;&#x09CD;&#x09B0;&#x09C7;&#x09A1;</th></tr>
            </thead>
            <tbody>
                <tr><td>80-100</td><td>A+</td></tr>
                <tr><td>70-79</td><td>A</td></tr>
                <tr><td>60-69</td><td>A-</td></tr>
                <tr><td>50-59</td><td>B</td></tr>
                <tr><td>40-49</td><td>C</td></tr>
                <tr><td>1-39</td><td>F</td></tr>
            </tbody>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Student ID:</strong> {{ $studentEnrollment->student->student_id ?? '-' }}</td>
            <td><strong>Student Name:</strong> {{ $studentEnrollment->student->name ?? 'Student #' . $studentEnrollment->student_id }}</td>
            <td><strong>Class:</strong> {{ $examAssessmentClass->schoolClass->name }}</td>
            <td><strong>Roll No:</strong> {{ $studentEnrollment->roll_number ?? '-' }}</td>
            <td><strong>GPA:</strong> {{ number_format((float) $result->gpa, 2) }}</td>
            <td><strong>Grade:</strong> {{ $result->final_grade }}</td>
        </tr>
    </table>

    <table class="marks">
        <thead>
            <tr>
                <th class="left">Subject</th>
                <th>Full Marks</th>
                <th>Written</th>
                <th>MCQ</th>
                <th>Practical</th>
                <th>GPA</th>
                <th>Grade</th>
                <th>Class Test</th>
                <th>Total</th>
                <th>Highest</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjectRows as $subject)
                <tr>
                    <td class="left">{{ $subject['subject'] }}</td>
                    <td>{{ rtrim(rtrim(number_format((float) $subject['total_marks'], 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $subject['written_marks'] !== null ? number_format((float) $subject['written_marks'], 2) : '-' }}</td>
                    <td>{{ $subject['mcq_marks'] !== null ? number_format((float) $subject['mcq_marks'], 2) : '-' }}</td>
                    <td>{{ $subject['practical_marks'] !== null ? number_format((float) $subject['practical_marks'], 2) : '-' }}</td>
                    <td>{{ $subject['term_gpa'] !== null ? number_format((float) $subject['term_gpa'], 2) : '-' }}</td>
                    <td>{{ $subject['term_grade'] ?? '-' }}</td>
                    <td>{{ number_format((float) ($subject['class_test_average'] ?? 0), 2) }}</td>
                    <td>{{ $subject['is_absent'] ? '-' : number_format((float) ($subject['obtained_marks'] ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($highestFinalMarksBySubject[$subject['subject_id']] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <th>Total (Exam)</th>
            <th>Hand Work - 20 (Home Work)</th>
            <th>Attendance Mark - 20</th>
            <th>Grand Total</th>
            <th>Percentage</th>
        </tr>
        <tr>
            <td>{{ number_format((float) $result->total_obtained, 2) }}</td>
            <td>{{ number_format((float) ($extraMarks['homework_marks'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) ($extraMarks['attendance_marks'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) $result->total_obtained + (float) ($extraMarks['homework_marks'] ?? 0) + (float) ($extraMarks['attendance_marks'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) $result->percentage, 2) }}%</td>
        </tr>
    </table>

    <table class="attendance-row">
        <tr>
            <td><strong>Total Working Days:</strong> <span class="line"></span></td>
            <td><strong>Present:</strong> <span class="line"></span></td>
            <td><strong>Absent:</strong> <span class="line"></span></td>
            <td><strong>Position:</strong> {{ $result->effective_position ?? ($result->position ?? '-') }}</td>
        </tr>
    </table>        <table class="manual evaluation">
            <thead>
                <tr>
                    <th colspan="13">Evaluation</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label">Study</td>
                    <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
                    <td class="label">Speech</td>
                    <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
                </tr>
                <tr>
                    <td class="label">Dress & Hygiene</td>
                    <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
                    <td class="label">Behavior</td>
                    <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
                </tr>
                <tr>
                    <td class="label">Cleanliness</td>
                    <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
                    <td class="label">Other Learning</td>
                    <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
                </tr>
                <tr>
                    <td colspan="14" class="muted">
                        A = Excellent &nbsp;&nbsp; B = Good &nbsp;&nbsp; C = Average &nbsp;&nbsp; D = Weak &nbsp;&nbsp; E = Inattentive &nbsp;&nbsp; F = Very Weak
                    </td>
                </tr>
            </tbody>
        </table>

    <div class="comment-box">
        <strong>Teacher Comment:</strong>
    </div>

    <table class="sign-row">
        <tr>
            <td>Director Signature</td>
            <td>Class Teacher Signature</td>
            <td>Guardian Signature</td>
        </tr>
    </table>
</div>
</body>
</html>
