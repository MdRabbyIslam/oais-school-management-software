<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentTermExtraMarksRequest;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Models\StudentTermExtraMark;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentTermExtraMarkController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-exams');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();

        $enrollments = collect();
        $existingMarks = collect();

        if ($request->filled('academic_year_id') && $request->filled('class_id')) {
            $academicYearId = (int) $request->input('academic_year_id');
            $classId = (int) $request->input('class_id');

            $enrollments = StudentEnrollment::with('student')
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->where('status', 'active')
                ->orderByRaw('ISNULL(roll_number), roll_number ASC')
                ->get();

            if ($request->filled('term_id')) {
                $existingMarks = StudentTermExtraMark::query()
                    ->where('academic_year_id', $academicYearId)
                    ->where('term_id', (int) $request->input('term_id'))
                    ->where('class_id', $classId)
                    ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                    ->get()
                    ->keyBy('student_enrollment_id');
            }
        }

        return view('pages.student_term_extra_marks_index', compact(
            'academicYears',
            'terms',
            'classes',
            'enrollments',
            'existingMarks'
        ));
    }

    public function store(StoreStudentTermExtraMarksRequest $request)
    {
        $this->authorize('manage-exams');

        DB::transaction(function () use ($request) {
            $academicYearId = (int) $request->input('academic_year_id');
            $termId = (int) $request->input('term_id');
            $classId = (int) $request->input('class_id');

            foreach ($request->input('rows', []) as $row) {
                StudentTermExtraMark::updateOrCreate(
                    [
                        'academic_year_id' => $academicYearId,
                        'term_id' => $termId,
                        'class_id' => $classId,
                        'student_enrollment_id' => (int) $row['student_enrollment_id'],
                    ],
                    [
                        'homework_marks' => ($row['homework_marks'] ?? '') === '' ? null : $row['homework_marks'],
                        'attendance_marks' => ($row['attendance_marks'] ?? '') === '' ? null : $row['attendance_marks'],
                        'remarks' => $row['remarks'] ?? null,
                        'entered_by' => (int) auth()->id(),
                        'entered_at' => now(),
                    ]
                );
            }
        });

        return redirect()
            ->route('student-term-extra-marks.index', [
                'academic_year_id' => $request->input('academic_year_id'),
                'term_id' => $request->input('term_id'),
                'class_id' => $request->input('class_id'),
            ])
            ->with('success', 'Additional marks saved successfully.');
    }
}

