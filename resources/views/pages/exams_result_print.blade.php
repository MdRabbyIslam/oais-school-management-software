<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Print (Nursery - Class 2)</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'SutonnyOMJ';
            src: url('{{ asset("upload/fonts/SutonnyOMJ.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Times';
            src: url('{{ asset("upload/fonts/times.ttf") }}') format('truetype');
        }

        :root {
            --navy:     #0e1b3d;
            --gold:     #b8922a;
            --gold-lt:  #d4aa50;
            --ink:      #111111;
            --ink-mid:  #2c2c2c;
            --rule:     #444444;
            --bg:       #ffffff;
            --font-scale: 1.12;
        }

        @page { size: A4 portrait; margin: 12mm 5mm 5mm 5mm; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'EB Garamond', 'Times New Roman', serif;
            color: var(--ink);
            background: #ccc;
        }

        .actions { padding: 8px; }
        .actions button {
            padding: 6px 16px;
            background: var(--navy);
            color: #fff;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: calc(13px * var(--font-scale));
        }

        /* ════════════════════════════════════════
           PRINT-SAFE FRAME  — pure line art, no fills
           Layer 1 (outermost): 2.5px solid black
           Layer 2:             0.5px solid black  (+5px gap)
           Layer 3 (innermost): 1px solid black    (+4px gap)
           Corner ornaments via SVG (stroke only)
        ════════════════════════════════════════ */
        .shell {
            background: #fff;
            padding: 0;
            position: relative;
            /* Layer 1 */
            border: 2.5px solid var(--ink);
        }
        /* Layer 2 */
        .shell::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 0.75px solid var(--ink);
            pointer-events: none;
            z-index: 2;
        }
        /* Layer 3 */
        .shell::after {
            content: '';
            position: absolute;
            inset: 9px;
            border: 1.5px solid var(--ink);
            pointer-events: none;
            z-index: 2;
        }

        /* ─── Parchment body ─── */
        .page {
            background: #fff;
            padding: 22px 20px 18px;
            position: relative;
        }

        /* placeholder divs no longer needed for borders */
        .border-outer, .border-inner { display: none; }

        /* ─── Corner ornaments ─── */
        .corner {
            position: absolute;
            width: 38px;
            height: 38px;
            pointer-events: none;
            z-index: 3;
            background: #fff;
        }
        .corner svg { display: block; width: 100%; height: 100%; }
        .corner.tl { top: -2px;    left: -2px;  }
        .corner.tr { top: -2px;    right: -2px; transform: scaleX(-1); }
        .corner.bl { bottom: -2px; left: -2px;  transform: scaleY(-1); }
        .corner.br { bottom: -2px; right: -2px; transform: scale(-1,-1); }

        /* ─── Content wrapper ─── */
        .content { position: relative; z-index: 1; padding: 8px 14px 6px; }

        /* ─── Watermark crest ─── */
        .watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: .1;
            pointer-events: none;
            z-index: 0;
        }
        .watermark img {
            width: 320px;
            height: 320px;
            object-fit: contain;
        }

        /* ─── Header ─── */
        .official-label {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: calc(8.5px * var(--font-scale));
            letter-spacing: 5px;
            color: #333333;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }

        .logo-wrap { width: 130px; text-align: center; flex-shrink: 0; }
        .logo-wrap img { max-width: 110px; max-height: 110px; }

        .title { flex: 1; text-align: center; }
        .bismillah { font-family: 'SutonnyOMJ', serif; font-size: calc(12px * var(--font-scale)); margin-bottom: 8px; color: #1c1c1c; }
        .school-bn {
            font-family: 'SutonnyOMJ', serif;
            font-size: calc(42px * var(--font-scale));
            font-weight: 700;
            line-height: 1;
            color: #1c1c1c;
            display: inline-block;
            transform: scaleX(1.45);
        }
        .school-en {
            font-family: 'Times', serif;
            font-size: calc(35px * var(--font-scale));
            font-weight: 700;
            line-height: 1.2;
            color: #1c1c1c;
            letter-spacing: 3px;
            margin-top: -4px;
        }
        .motto {
            font-family: 'SutonnyOMJ', serif;
            font-size: calc(12px * var(--font-scale));
            color: #333;
            margin-top: 4px;
        }

        /* Divider — solid rules, no gradient */
        .divider {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 8px 0;
        }
        .divider-line { flex: 1; height: 1px; background: #555; }
        .divider-diamond {
            width: 5px; height: 5px;
            background: #333;
            transform: rotate(45deg);
            flex-shrink: 0;
        }

        .term-box {
            display: inline-block;
            border: 1.5px solid #1c1c1c;
            padding: 4px 18px;
            margin-top: 6px;
            font-family: 'Cinzel', serif;
            font-size: calc(14px * var(--font-scale));
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #1c1c1c;
            position: relative;
        }
        .term-box::before, .term-box::after {
            content: '✦';
            position: absolute;
            top: 50%; transform: translateY(-50%);
            color: #555;
            font-size: calc(9px * var(--font-scale));
        }
        .term-box::before { left: 5px; }
        .term-box::after  { right: 5px; }

        /* Grade legend box */
        .grade-box { border-collapse: collapse; width: 130px; flex-shrink: 0; }
        .grade-box caption {
            font-family: 'Cinzel', serif;
            font-size: 9px;
            letter-spacing: 2px;
            color: #333;
            padding-bottom: 4px;
            text-transform: uppercase;
        }
        .grade-box th, .grade-box td {
            border: .5px solid #aaaaaa;
            padding: 2.5px 6px;
            font-size: calc(11.5px * var(--font-scale));
            text-align: center;
        }
        .grade-box thead tr {
            background: #1c1c1c;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .grade-box thead th { color: #ffffff; font-family: 'Cinzel', serif; font-size: calc(10px * var(--font-scale)); letter-spacing: .5px; }
        .grade-box tbody tr:nth-child(odd) { background: #f4f4f4; }

        /* ─── Info / meta table ─── */
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta td {
            border: .5px solid #aaaaaa;
            padding: 5px 7px;
            font-size: calc(12.5px * var(--font-scale));
        }
        .meta td:first-child { border-left: 3px solid #1c1c1c; }
        .meta strong { color: #1c1c1c; font-family: 'Cinzel', serif; font-size: calc(10.5px * var(--font-scale)); display: block; margin-bottom: 1px; letter-spacing: .3px; }

        /* ─── Section header strip ─── */
        .sec-header {
            background: #1c1c1c;
            color: #ffffff;
            font-family: 'Cinzel', serif;
            font-size: calc(9px * var(--font-scale));
            letter-spacing: 3px;
            text-align: center;
            padding: 5px 4px;
            text-transform: uppercase;
            margin-bottom: -1px;
            border-left: 3px solid #555;
            border-right: 3px solid #555;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ─── Marks table ─── */
        .marks, .summary-table, .manual {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .marks thead tr th {
            background: #1c1c1c;
            color: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .marks th, .marks td,
        .summary-table th, .summary-table td,
        .manual th, .manual td {
            border: .5px solid #aaaaaa;
            padding: 4px 5px;
            font-size: calc(12px * var(--font-scale));
            text-align: center;
        }
        .marks thead th {
            font-family: 'Cinzel', serif;
            font-size: calc(9.5px * var(--font-scale));
            letter-spacing: .4px;
            padding: 5px 5px;
        }
        .marks tbody tr:nth-child(even) { background: #f4f4f4; }
        .marks tbody tr:hover { background: #ececec; }
        .left { text-align: left !important; padding-left: 8px !important; }

        .summary-table thead th {
            background: #3a3a3a;
            color: #ffffff;
            font-family: 'Cinzel', serif;
            font-size: calc(9.5px * var(--font-scale));
            letter-spacing: .5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .summary-table tbody td { font-weight: 600; font-size: calc(13px * var(--font-scale)); }

        /* ─── Attendance ─── */
        .attendance-row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .attendance-row td {
            border: .5px solid #aaaaaa;
            padding: 5px 8px;
            font-size: calc(12.5px * var(--font-scale));
        }
        .attendance-row td strong {
            font-family: 'Cinzel', serif;
            font-size: calc(10px * var(--font-scale));
            color: #1c1c1c;
            display: inline-block;
            margin-bottom: 0;
            margin-right: 6px;
            vertical-align: middle;
        }
        .line { display: inline-block; border-bottom: 1px dotted #888; min-width: 80px; height: 16px; vertical-align: middle; }

        /* ─── Evaluation grid ─── */
        .manual thead th {
            background: #1c1c1c;
            color: #ffffff;
            font-family: 'Cinzel', serif;
            font-size: calc(9.5px * var(--font-scale));
            letter-spacing: 2px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .eval-grade {
            width: 24px;
            height: 22px;
            background: #f4f4f4;
            border-radius: 2px;
            font-weight: 600;
            color: #1c1c1c;
        }
        .label { text-align: left !important; padding-left: 8px !important; font-size: calc(11.5px * var(--font-scale)); font-weight: 600; color: #1c1c1c; }
        .legend-row { display: flex; justify-content: space-between; gap: 8px; font-size: calc(10.5px * var(--font-scale)); font-style: italic; color: #000; }

        /* ─── Comment box ─── */
        .comment-box {
            width: 100%;
            border: .5px solid #aaaaaa;
            border-left: 3px solid #1c1c1c;
            min-height: 90px;
            margin-bottom: 8px;
            padding: 6px 10px;
            font-size: calc(12.5px * var(--font-scale));
        }
        .comment-box strong {
            font-family: 'Cinzel', serif;
            font-size: calc(10px * var(--font-scale));
            color: #1c1c1c;
            letter-spacing: .5px;
            display: block;
            margin-bottom: 4px;
        }

        /* ─── Signature row ─── */
        .sign-row { width: 100%; border-collapse: collapse; }
        .sign-row td {
            border: .5px solid #aaaaaa;
            height: 100px;
            padding: 6px 8px;
            vertical-align: bottom;
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: calc(9.5px * var(--font-scale));
            letter-spacing: .5px;
            color: #1c1c1c;;
        }
        .sign-row td::before {
            content: '';
            display: block;
            width: 70%;
            margin: 0 auto 6px;
            border-bottom: 1px solid #666;
        }

        /* ─── Footer seal ─── */
        .footer-seal {
            text-align: center;
            margin-top: 6px;
            font-family: 'Cinzel', serif;
            font-size: calc(7.5px * var(--font-scale));
            letter-spacing: 3px;
            color: #555;
            text-transform: uppercase;
        }

        @media print {
            .actions { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="actions">
    <button type="button" onclick="window.print()">🖨 Print</button>
</div>

<div class="shell">

    <!-- Corner ornaments — stroke only, print-safe -->
    <div class="corner tl">
        <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- outer L -->
            <polyline points="38,2.5 2.5,2.5 2.5,38" stroke="#111" stroke-width="2.5"/>
            <!-- mid L -->
            <polyline points="38,7.5 7.5,7.5 7.5,38" stroke="#111" stroke-width="0.75"/>
            <!-- inner L -->
            <polyline points="38,11.5 11.5,11.5 11.5,38" stroke="#111" stroke-width="1.5"/>
            <!-- decorative cross-hatch at corner -->
            <line x1="11.5" y1="11.5" x2="2.5" y2="2.5" stroke="#111" stroke-width="0.6"/>
            <!-- small diamond -->
            <rect x="9" y="9" width="5" height="5" transform="rotate(45 11.5 11.5)" fill="#111" stroke="none"/>
        </svg>
    </div>
    <div class="corner tr">
        <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
            <polyline points="38,2.5 2.5,2.5 2.5,38" stroke="#111" stroke-width="2.5"/>
            <polyline points="38,7.5 7.5,7.5 7.5,38" stroke="#111" stroke-width="0.75"/>
            <polyline points="38,11.5 11.5,11.5 11.5,38" stroke="#111" stroke-width="1.5"/>
            <line x1="11.5" y1="11.5" x2="2.5" y2="2.5" stroke="#111" stroke-width="0.6"/>
            <rect x="9" y="9" width="5" height="5" transform="rotate(45 11.5 11.5)" fill="#111" stroke="none"/>
        </svg>
    </div>
    <div class="corner bl">
        <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
            <polyline points="38,2.5 2.5,2.5 2.5,38" stroke="#111" stroke-width="2.5"/>
            <polyline points="38,7.5 7.5,7.5 7.5,38" stroke="#111" stroke-width="0.75"/>
            <polyline points="38,11.5 11.5,11.5 11.5,38" stroke="#111" stroke-width="1.5"/>
            <line x1="11.5" y1="11.5" x2="2.5" y2="2.5" stroke="#111" stroke-width="0.6"/>
            <rect x="9" y="9" width="5" height="5" transform="rotate(45 11.5 11.5)" fill="#111" stroke="none"/>
        </svg>
    </div>
    <div class="corner br">
        <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
            <polyline points="38,2.5 2.5,2.5 2.5,38" stroke="#111" stroke-width="2.5"/>
            <polyline points="38,7.5 7.5,7.5 7.5,38" stroke="#111" stroke-width="0.75"/>
            <polyline points="38,11.5 11.5,11.5 11.5,38" stroke="#111" stroke-width="1.5"/>
            <line x1="11.5" y1="11.5" x2="2.5" y2="2.5" stroke="#111" stroke-width="0.6"/>
            <rect x="9" y="9" width="5" height="5" transform="rotate(45 11.5 11.5)" fill="#111" stroke="none"/>
        </svg>
    </div>

    <div class="page">
        <!-- Inner border lines -->
        <div class="border-outer"></div>
        <div class="border-inner"></div>

        <!-- Background watermark -->
        <div class="watermark">
            <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="School watermark">
        </div>

        <div class="content">
            <!-- Official label -->
            <div class="official-label">✦ &nbsp; Official Academic Result Sheet &nbsp; ✦</div>

            <!-- TOP ROW -->
            <div class="top">
                <div class="logo-wrap">
                    <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="School Logo">
                </div>

                <div class="title">
                    <div class="bismillah">বিসমিল্লাহির রাহমানির রাহিম</div>
                    <div class="school-bn">ওয়েসিস মডেল স্কুল</div>
                    <div class="school-en">Oasis Model School</div>
                    <div class="motto">"মানসম্মত শিক্ষার পাশাপাশি আদর্শ মানুষ হিসেবে ছাত্র-ছাত্রীদের গড়ে তোলাই আমাদের লক্ষ্য।"</div>
                    <div class="term-box">{{ strtoupper($examAssessmentClass->examAssessment->name) }}</div>
                </div>

                <table class="grade-box">
                    <caption>Grading Scale</caption>
                    <thead><tr><th>Marks</th><th>Grade</th></tr></thead>
                    <tbody>
                        <tr><td>90–100</td><td>A+</td></tr>
                        <tr><td>80–89</td><td>A</td></tr>
                        <tr><td>70–79</td><td>A−</td></tr>
                        <tr><td>60–69</td><td>B+</td></tr>
                        <tr><td>50–59</td><td>B</td></tr>
                        <tr><td>40–49</td><td>C</td></tr>
                        <tr><td>1–39</td><td>F</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Gold divider -->
            <div class="divider">
                <div class="divider-line"></div>
                <div class="divider-diamond"></div>
                <div class="divider-line"></div>
                <div class="divider-diamond"></div>
                <div class="divider-line"></div>
            </div>

            <!-- META / Student info -->
            <table class="meta">
                <tr>
                    <td><strong>Student ID</strong>{{ $studentEnrollment->student->student_id ?? '-' }}</td>
                    <td><strong>Student Name</strong>{{ $studentEnrollment->student->name ?? 'Student #' . $studentEnrollment->student_id }}</td>
                    <td><strong>Class</strong>{{ $examAssessmentClass->schoolClass->name }}</td>
                    <td><strong>Roll No.</strong>{{ $studentEnrollment->roll_number ?? '-' }}</td>
                    
                </tr>
            </table>

            <!-- Marks section header -->
            <div class="sec-header">✦ &nbsp; Subject-wise Performance &nbsp; ✦</div>

            <!-- MARKS TABLE -->
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
                        <td>{{ $subject['is_absent'] ? '-' : number_format((float) ($subject['term_obtained_marks'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($highestTermMarksBySubject[$subject['subject_id']] ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- SUMMARY TABLE -->
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Total (Exam)</th>
                        <th>Total (C.T.)</th>
                        <th>Hand Work — 20</th>
                        <th>Attendance — 20</th>
                        <th>Grand Total</th>
                        <th>Percentage</th>
                        <th>GPA</th>
                        <th>Grade</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ number_format((float) ($summaryTotals['exam_total'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($summaryTotals['class_test_total'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($summaryTotals['homework_marks'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($summaryTotals['attendance_marks'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($summaryTotals['grand_total'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) $result->percentage, 2) }}%</td>
                        <td>{{ number_format((float) $result->gpa, 2) }}</td>
                        <td>{{ $result->final_grade }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- ATTENDANCE -->
            <table class="attendance-row">
                <tr>
                    <td><strong>Total Working Days</strong><span class="line"></span></td>
                    <td><strong>Present</strong><span class="line"></span></td>
                    <td><strong>Absent</strong><span class="line"></span></td>
                    <td><strong>Position</strong>{{ $result->effective_position ?? ($result->position ?? '-') }}</td>
                </tr>
            </table>

            <!-- EVALUATION -->
            <div class="sec-header">✦ &nbsp; Evaluation &nbsp; ✦</div>
            <table class="manual">
                <tbody>
                    <tr>
                        <td class="label">Study</td>
                        <td class="eval-grade">A</td><td class="eval-grade">B</td><td class="eval-grade">C</td><td class="eval-grade">D</td><td class="eval-grade">E</td><td class="eval-grade">F</td>
                        <td class="label">Discipline</td>
                        <td class="eval-grade">A</td><td class="eval-grade">B</td><td class="eval-grade">C</td><td class="eval-grade">D</td><td class="eval-grade">E</td><td class="eval-grade">F</td>
                    </tr>
                    <tr>
                        <td class="label">Dress Code</td>
                        <td class="eval-grade">A</td><td class="eval-grade">B</td><td class="eval-grade">C</td><td class="eval-grade">D</td><td class="eval-grade">E</td><td class="eval-grade">F</td>
                        <td class="label">Behavior</td>
                        <td class="eval-grade">A</td><td class="eval-grade">B</td><td class="eval-grade">C</td><td class="eval-grade">D</td><td class="eval-grade">E</td><td class="eval-grade">F</td>
                    </tr>
                    <tr>
                        <td class="label">Cleanliness</td>
                        <td class="eval-grade">A</td><td class="eval-grade">B</td><td class="eval-grade">C</td><td class="eval-grade">D</td><td class="eval-grade">E</td><td class="eval-grade">F</td>
                        <td class="label">Other Learning</td>
                        <td class="eval-grade">A</td><td class="eval-grade">B</td><td class="eval-grade">C</td><td class="eval-grade">D</td><td class="eval-grade">E</td><td class="eval-grade">F</td>
                    </tr>
                    <tr>
                        <td colspan="14">
                            <div class="legend-row">
                                <span>A = Excellent</span><span>B = Good</span><span>C = Average</span>
                                <span>D = Weak</span><span>E = Inattentive</span><span>F = Very Weak</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- COMMENT -->
            <div class="comment-box">
                <strong>Teacher's Comment</strong>
            </div>

            <!-- SIGNATURES -->
            <table class="sign-row">
                <tr>
                    <td> <strong> Director's Signature </strong> </td>
                    <td> <strong> Class Teacher's Signature </strong> </td>
                    <td> <strong> Guardian's Signature </strong> </td>
                </tr>
            </table>

            <!-- Footer -->
            <div class="footer-seal">✦ &nbsp; Oasis Model School &nbsp;·&nbsp; Official Record &nbsp; ✦</div>
        </div><!-- /content -->
    </div><!-- /page -->
</div><!-- /shell -->

</body>
</html>
