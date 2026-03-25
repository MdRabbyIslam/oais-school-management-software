<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Print (Nursery - Class 2)</title>
    <style>
        @page { size: A4 portrait; margin: 7mm; }
        body { font-family: "Times New Roman", serif; color: #111; margin: 0; }
        .wrap { border: 1px solid #222; padding: 10px; }
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
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.6px;
        }
        .grade-box { width: 210px; border-collapse: collapse; }
        .grade-box th, .grade-box td { border: 1px solid #222; padding: 3px 6px; font-size: 12px; text-align: center; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta td { border: 1px solid #222; padding: 5px 6px; font-size: 14px; min-height: 24px; }
        .line { display: inline-block; border-bottom: 1px dotted #666; min-width: 120px; height: 16px; vertical-align: bottom; }
        .marks { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .marks th, .marks td { border: 1px solid #222; padding: 4px 5px; font-size: 13px; text-align: center; }
        .marks th { font-weight: 700; }
        .left { text-align: left !important; }
        .attendance-row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .attendance-row td { border: 1px solid #222; padding: 5px 6px; font-size: 14px; }
        .evaluation { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .evaluation th, .evaluation td { border: 1px solid #222; padding: 4px 5px; font-size: 13px; text-align: center; }
        .evaluation .label { width: 22%; text-align: left; }
        .evaluation .muted { font-size: 12px; }
        .comment-box { width: 100%; border: 1px solid #222; min-height: 70px; margin-bottom: 8px; padding: 6px; font-size: 13px; }
        .sign-row { width: 100%; border-collapse: collapse; }
        .sign-row td { border: 1px solid #222; height: 42px; padding: 4px 6px; font-size: 13px; vertical-align: bottom; }
        .tiny { font-size: 12px; }
        @media print {
            .actions { display: none; }
            .wrap { padding: 10px; }
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
                <div class="school-bn">ওয়েসিস মডেল স্কুল</div>
                <div class="school-en">Oasis Model School</div>
                <div class="motto">"মানসম্মত শিক্ষা পাশাপাশি আদর্শ মানুষ হিসেবে ছাত্র-ছাত্রী গড়ে আমাদের লক্ষ্য"</div>
                <div class="term-box">{{ strtoupper($examAssessmentClass->examAssessment->name) }}</div>
            </div>
            <table class="grade-box">
                <thead>
                    <tr><th>নম্বর</th><th>গ্রেড</th></tr>
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
                <td><strong>আইডি নং-</strong> {{ $studentEnrollment->student->student_id ?? '-' }}</td>
                <td><strong>শিক্ষার্থীর নামঃ</strong> {{ $studentEnrollment->student->name ?? 'Student #' . $studentEnrollment->student_id }}</td>
                <td><strong>শ্রেণীঃ</strong> {{ $examAssessmentClass->schoolClass->name }}</td>
                <td><strong>রোল নং-</strong> {{ $studentEnrollment->roll_number ?? '-' }}</td>
            </tr>
        </table>

        <table class="marks">
            <thead>
                <tr>
                    <th rowspan="2">বিষয়</th>
                    <th rowspan="2">পূর্ণমান</th>
                    <th colspan="4">প্রাপ্ত নম্বর</th>
                    <th rowspan="2">সর্বমোট নম্বর</th>
                    <th rowspan="2">সর্বোচ্চ নম্বর</th>
                </tr>
                <tr>
                    <th>পর্ব পরীক্ষা</th>
                    <th>গ্রেড</th>
                    <th>G.P</th>
                    <th>শ্রেণী পরীক্ষা</th>
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
                <tr>
                    <td class="left"><strong>হাতে কাজ</strong></td>
                    <td>20</td>
                    <td>-</td><td></td><td></td>
                    <td>-</td>
                    <td>{{ number_format((float) ($extraMarks['homework_marks'] ?? 0), 2) }}</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="left"><strong>উপস্থিতি</strong></td>
                    <td>20</td>
                    <td>-</td><td></td><td></td>
                    <td>-</td>
                    <td>{{ number_format((float) ($extraMarks['attendance_marks'] ?? 0), 2) }}</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="left"><strong>সর্বমোটঃ</strong></td>
                    <td colspan="5"></td>
                    <td><strong>{{ number_format((float) $result->total_obtained + (float) ($extraMarks['homework_marks'] ?? 0) + (float) ($extraMarks['attendance_marks'] ?? 0), 2) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <table class="attendance-row">
            <tr>
                <td><strong>মোট কার্য দিবসঃ</strong> <span class="line"></span></td>
                <td><strong>উপস্থিতিঃ</strong> <span class="line"></span></td>
                <td><strong>অনুপস্থিতিঃ</strong> <span class="line"></span></td>
                <td><strong>G.P.A:</strong> {{ number_format((float) $result->gpa, 2) }}</td>
                <td><strong>স্থানঃ</strong> <span class="line" style="min-width:80px;"></span></td>
            </tr>
        </table>

        <table class="evaluation">
            <thead>
                <tr>
                    <th colspan="13">মূল্যায়ন</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label">লেখাপড়া</td>
                    <td>ক</td><td>খ</td><td>গ</td><td>ঘ</td><td>ঙ</td><td>চ</td>
                    <td class="label">কথা</td>
                    <td>ক</td><td>খ</td><td>গ</td><td>ঘ</td><td>ঙ</td><td>চ</td>
                </tr>
                <tr>
                    <td class="label">পোশাক পরিচ্ছন্নতা</td>
                    <td>ক</td><td>খ</td><td>গ</td><td>ঘ</td><td>ঙ</td><td>চ</td>
                    <td class="label">আচরণ</td>
                    <td>ক</td><td>খ</td><td>গ</td><td>ঘ</td><td>ঙ</td><td>চ</td>
                </tr>
                <tr>
                    <td class="label">পরিচ্ছন্নতা</td>
                    <td>ক</td><td>খ</td><td>গ</td><td>ঘ</td><td>ঙ</td><td>চ</td>
                    <td class="label">অন্যান্য শিক্ষা</td>
                    <td>ক</td><td>খ</td><td>গ</td><td>ঘ</td><td>ঙ</td><td>চ</td>
                </tr>
                <tr>
                    <td colspan="14" class="muted">
                        ক = উত্তম &nbsp;&nbsp; খ = ভালো &nbsp;&nbsp; গ = মধ্যম &nbsp;&nbsp; ঘ = দুর্বল &nbsp;&nbsp; ঙ = অমনোযোগী &nbsp;&nbsp; চ = খুবই দুর্বল
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="comment-box">
            <strong>শ্রেণী শিক্ষকের মন্তব্যঃ</strong>
        </div>

        <table class="sign-row">
            <tr>
                <td class="tiny">পরিচালকের স্বাক্ষর</td>
                <td class="tiny">শ্রেণী শিক্ষকের স্বাক্ষর</td>
                <td class="tiny">অভিভাবকের স্বাক্ষর</td>
            </tr>
        </table>
    </div>
</body>
</html>




