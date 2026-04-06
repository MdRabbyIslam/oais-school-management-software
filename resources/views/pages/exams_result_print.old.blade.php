<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Print (Nursery - Class 2)</title>
    <style>
        @font-face {
            font-family: 'SutonnyOMJ';
            src: url('{{ asset('upload/fonts/SutonnyOMJ.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Times';
            src: url('{{ asset('upload/fonts/times.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }

        @page { size: A4 portrait; margin: 7mm; }
        body { font-family: "Times", serif; color: #111; margin: 0; background: #f5f1e8; }
        .wrap { box-sizing: border-box; }
        .actions { margin-bottom: 8px; }
        .actions button { padding: 6px 12px; }
        .frame {
            position: relative;
            padding: 10px;
            background: #fdfbf6;
            border: 2px solid #2f2a24;
        }
        .frame::before {
            content: "";
            position: absolute;
            inset: 8px;
            border: 1px solid #9f947f;
            pointer-events: none;
        }
        .frame::after {
            content: "";
            position: absolute;
            inset: 16px;
            border: 1px solid #2f2a24;
            pointer-events: none;
        }
        .frame-inner {
            position: relative;
            background:
                linear-gradient(#fffdf8, #fffdf8) padding-box,
                repeating-linear-gradient(
                    90deg,
                    rgba(47, 42, 36, 0.12) 0,
                    rgba(47, 42, 36, 0.12) 2px,
                    transparent 2px,
                    transparent 12px
                ) border-box;
            border: 1px solid transparent;
            padding: 24px 20px 18px;
        }
        .frame-accent {
            margin-bottom: 14px;
            text-align: center;
            font-size: 11px;
            letter-spacing: 4px;
            color: #5a5146;
            text-transform: uppercase;
        }

        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px; }
        .logo-wrap { width: 140px; text-align: center; }
        .logo-wrap img { max-width: 120px; max-height: 120px; }
        .title { flex: 1; text-align: center; }
        .bismillah { font-size: 12px; margin-bottom: 10px;font-family: "SutonnyOMJ", serif; }
        .school-bn {
            font-family: "SutonnyOMJ", serif;
            font-size: 45px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 2px;
            transform: scaleX(1.5);
        }
        .school-en {
            font-family: "Times", serif;
            font-size: 35px;
            font-weight: 700;
            line-height: 1;
            margin-top: 2px;
            transform: scaleX(1.5);
        }
        .motto { font-size: 12px; margin-top: 4px; font-family: "SutonnyOMJ", serif;}
        .term-box {
            display: inline-block;
            border: 1px solid #222;
            padding: 3px 12px;
            margin-top: 8px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .grade-box { width: 150px; border-collapse: collapse; }
        .grade-box th, .grade-box td { border: 1px solid #222; padding: 3px 6px; font-size: 12px; text-align: center; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta td { border: 1px solid #222; padding: 5px 6px; font-size: 14px; }

        .marks,
        .summary-table,
        .manual {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .marks th, .marks td,
        .summary-table th, .summary-table td,
        .manual th, .manual td {
            border: 1px solid #222;
            padding: 4px 5px;
            font-size: 13px;
            text-align: center;
        }

        .marks th { font-weight: 700; }
        .left { text-align: left !important; }

        .attendance-row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .attendance-row td { border: 1px solid #222; padding: 5px 6px; font-size: 14px; }
        .line { display: inline-block; border-bottom: 1px dotted #666; min-width: 90px; height: 16px; vertical-align: bottom; }

        .comment-box { width: 100%; border: 1px solid #222; min-height: 84px; margin-bottom: 8px; padding: 0; font-size: 13px; }
        .comment-box strong{
            padding: 4px 6px;
        }
        .sign-row { width: 100%; border-collapse: collapse; }
        .sign-row td { border: 1px solid #222; height: 84px; padding: 4px 6px; font-size: 13px; vertical-align: bottom; text-align: center; }
        .muted { font-size: 12px; }
        .label { width: 22%; text-align: left; }
        .legend-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
        }
        .legend-row span {
            white-space: nowrap;
        }

        @media print {
            .actions { display: none; }
            body { background: #fff; }
            .frame-inner { padding: 20px 16px 14px; }
        }
    </style>
</head>
<body onload="window.print()">
<div class="wrap">
    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="frame">
    <div class="frame-inner">
    <div class="frame-accent">Official Academic Result Sheet</div>
    <div class="top">
        <div class="logo-wrap">
            <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="School Logo">
        </div>
        <div class="title">
            <div class="bismillah">বিসমিল্লাহির রাহমানির রাহিম</div>
            <div class="school-bn">ওয়েসিস মডেল স্কুল</div>
            <div class="school-en">Oasis Model School</div>
            <div class="motto">“মানসম্মত শিক্ষার পাশাপাশি আদর্শ মানুষ হিসেবে ছাত্র-ছাত্রীদের গড়ে তোলাই আমাদের লক্ষ্য।”</div>
            <div class="term-box">{{ strtoupper($examAssessmentClass->examAssessment->name) }}</div>
        </div>
        <table class="grade-box">
            <thead>
                <tr><th>Marks</th><th>Grade</th></tr>
            </thead>
            <tbody>
                <tr><td>90-100</td><td>A+</td></tr>
                <tr><td>80-89</td><td>A</td></tr>
                <tr><td>70-79</td><td>A-</td></tr>
                <tr><td>60-69</td><td>B+</td></tr>
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
                <th>Term Exam</th>
                <th>Grade</th>
                <th>GPA</th>
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
                    <td>{{ $subject['is_absent'] ? 'ABSENT' : (($subject['term_obtained_marks'] !== null) ? number_format((float) $subject['term_obtained_marks'], 2) : '-') }}</td>
                    <td>{{ $subject['term_grade'] ?? '-' }}</td>
                    <td>{{ $subject['term_gpa'] !== null ? number_format((float) $subject['term_gpa'], 2) : '-' }}</td>
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
            <th>Hand Work - 20</th>
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
    </table>

    <table class="manual">
        <thead>
            <tr>
                <th colspan="14">Evaluation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label">Study</td>
                <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
                <td class="label">Discipline</td>
                <td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td>
            </tr>
            <tr>
                <td class="label">Dress Code</td>
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
                    <div class="legend-row">
                        <span>A = Excellent</span>
                        <span>B = Good</span>
                        <span>C = Average</span>
                        <span>D = Weak</span>
                        <span>E = Inattentive</span>
                        <span>F = Very Weak</span>
                    </div>
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
    </div>
</div>
</body>
</html>
