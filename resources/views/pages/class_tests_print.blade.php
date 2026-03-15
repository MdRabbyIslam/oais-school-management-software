<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Test Result - {{ $classTest->name }}</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            margin: 70px 14px 14px;
            color: #111827;
            background: #f8fafc;
        }
        .print-actions {
            position: fixed;
            top: 10px;
            right: 12px;
            z-index: 1000;
        }
        .print-actions button {
            border: 1px solid #111827;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            padding: 6px 10px;
            margin-left: 6px;
            cursor: pointer;
        }
        .sheet {
            background: #ffffff;
            border: 1px solid #d1d5db;
            padding: 10px 12px 12px;
        }
        .title {
            text-align: center;
            margin-bottom: 8px;
        }
        .title h1 {
            margin: 0;
            font-size: 34px;
            letter-spacing: 0.5px;
        }
        .title h2 {
            margin: 4px 0 0;
            font-size: 28px;
        }
        .title h3 {
            margin: 4px 0 0;
            font-size: 22px;
        }
        .meta {
            margin: 14px 0;
            font-size: 16px;
            display: grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            gap: 6px 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #111;
            padding: 6px 5px;
            font-size: 14px;
            text-align: center;
            word-wrap: break-word;
        }
        th {
            background: #f3f4f6;
            font-size: 15px;
        }
        td.student {
            text-align: left;
            font-weight: 700;
            font-size: 15px;
        }
        td.fail {
            color: #b91c1c;
            font-weight: 700;
        }
        td.pass {
            color: #065f46;
            font-weight: 700;
        }
        td.absent {
            color: #92400e;
            font-weight: 700;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body {
                margin: 0;
                background: #ffffff;
            }
            .print-actions {
                display: none;
            }
            .sheet {
                border: 0;
                padding: 0;
            }
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
            <h2>{{ $classTest->name }}</h2>
            <h3>Class Test Result Sheet</h3>
        </div>

        <div class="meta">
            <div><strong>Academic Year:</strong> {{ $classTest->academicYear->name ?? '-' }}</div>
            <div><strong>Term:</strong> {{ $classTest->term->name ?? '-' }}</div>
            <div><strong>Class:</strong> {{ $classTest->schoolClass->name ?? '-' }}</div>
            <div><strong>Subject:</strong> {{ $classTest->subject->name ?? '-' }}</div>
            <div><strong>Total Marks:</strong> {{ number_format((float) $classTest->total_marks, 2) }}</div>
            <div><strong>Pass Marks:</strong> {{ $classTest->pass_marks !== null ? number_format((float) $classTest->pass_marks, 2) : '-' }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 6%;">SL</th>
                    <th style="width: 12%;">Roll</th>
                    <th style="width: 34%;">Student Name</th>
                    <th style="width: 12%;">Obtained</th>
                    <th style="width: 16%;">Status</th>
                    <th style="width: 20%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    @php($statusClass = $row['status'] === 'PASS' ? 'pass' : ($row['status'] === 'FAIL' ? 'fail' : ($row['status'] === 'ABSENT' ? 'absent' : '')))
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['roll_number'] ?? '-' }}</td>
                        <td class="student">{{ $row['student_name'] }}</td>
                        <td>{{ $row['obtained'] !== null ? number_format((float) $row['obtained'], 2) : '-' }}</td>
                        <td class="{{ $statusClass }}">{{ $row['status'] }}</td>
                        <td>{{ $row['remarks'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No active students found for this class and academic year.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
