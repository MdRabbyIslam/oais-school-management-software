<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassTest;
use App\Models\ClassTestMark;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClassTestReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-exams');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();
        $subjects = Subject::orderBy('name')->get();

        $students = collect();
        if ($request->filled('academic_year_id') && $request->filled('class_id')) {
            $students = StudentEnrollment::with('student')
                ->where('academic_year_id', (int) $request->input('academic_year_id'))
                ->where('class_id', (int) $request->input('class_id'))
                ->where('status', 'active')
                ->orderByRaw('ISNULL(roll_number), roll_number ASC')
                ->get();
        }

        return view('pages.class_tests_reports_index', compact(
            'academicYears',
            'terms',
            'classes',
            'subjects',
            'students'
        ));
    }

    public function printAllStudents(Request $request)
    {
        $this->authorize('manage-exams');

        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
        ]);

        $classTests = $this->buildFilteredClassTestQuery($validated)
            ->with(['subject', 'term', 'academicYear', 'schoolClass'])
            ->orderByRaw('ISNULL(test_date), test_date ASC')
            ->orderBy('id')
            ->get();

        $students = StudentEnrollment::with('student')
            ->where('academic_year_id', (int) $validated['academic_year_id'])
            ->where('class_id', (int) $validated['class_id'])
            ->where('status', 'active')
            ->orderByRaw('ISNULL(roll_number), roll_number ASC')
            ->get();

        $marksByComposite = ClassTestMark::query()
            ->whereIn('class_test_id', $classTests->pluck('id'))
            ->whereIn('student_enrollment_id', $students->pluck('id'))
            ->get()
            ->keyBy(fn ($mark) => $mark->class_test_id . ':' . $mark->student_enrollment_id);

        $rows = $students->map(function ($student) use ($classTests, $marksByComposite) {
            $marks = $classTests->map(function ($test) use ($student, $marksByComposite) {
                $mark = $marksByComposite->get($test->id . ':' . $student->id);
                if (!$mark || $mark->is_absent) {
                    return null;
                }

                return (float) $mark->marks_obtained;
            });

            $average = $marks->filter(fn ($value) => $value !== null)->avg();

            return [
                'roll_number' => $student->roll_number,
                'student_name' => $student->student->name ?? ('Student #' . $student->student_id),
                'marks' => $marks->values(),
                'average' => $average,
            ];
        })->values();

        return view('pages.class_tests_reports_all_students_print', [
            'classTests' => $classTests,
            'rows' => $rows,
            'filters' => $validated,
            'filterSummary' => $this->buildFilterSummary($validated),
        ]);
    }

    public function printSingleStudent(Request $request)
    {
        $this->authorize('manage-exams');

        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'student_enrollment_id' => ['required', 'exists:student_enrollments,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
        ]);

        $studentEnrollment = StudentEnrollment::with('student')
            ->findOrFail((int) $validated['student_enrollment_id']);

        if (
            (int) $studentEnrollment->academic_year_id !== (int) $validated['academic_year_id']
            || (int) $studentEnrollment->class_id !== (int) $validated['class_id']
        ) {
            abort(422, 'Selected student does not belong to the selected academic year/class.');
        }

        $classTests = $this->buildFilteredClassTestQuery($validated)
            ->with(['subject', 'term', 'academicYear', 'schoolClass'])
            ->orderByRaw('ISNULL(test_date), test_date ASC')
            ->orderBy('id')
            ->get();

        $marksByTest = ClassTestMark::query()
            ->whereIn('class_test_id', $classTests->pluck('id'))
            ->where('student_enrollment_id', $studentEnrollment->id)
            ->get()
            ->keyBy('class_test_id');

        $rows = $classTests->map(function ($test) use ($marksByTest) {
            $mark = $marksByTest->get($test->id);
            $obtained = $mark && !$mark->is_absent ? (float) $mark->marks_obtained : null;
            $passMarks = $test->pass_marks !== null ? (float) $test->pass_marks : null;

            $status = 'N/A';
            if ($mark && $mark->is_absent) {
                $status = 'ABSENT';
            } elseif ($obtained !== null) {
                $status = $passMarks === null || $obtained >= $passMarks ? 'PASS' : 'FAIL';
            }

            return [
                'test_name' => $test->name,
                'subject_name' => $test->subject->name ?? ('Subject #' . $test->subject_id),
                'term_name' => $test->term->name ?? '-',
                'test_date' => optional($test->test_date)->format('d M Y'),
                'total_marks' => (float) $test->total_marks,
                'pass_marks' => $passMarks,
                'obtained' => $obtained,
                'status' => $status,
            ];
        })->values();

        $average = $rows->filter(fn ($row) => $row['obtained'] !== null)->avg('obtained');

        return view('pages.class_tests_reports_single_student_print', [
            'classTests' => $classTests,
            'studentEnrollment' => $studentEnrollment,
            'rows' => $rows,
            'average' => $average,
            'filters' => $validated,
            'filterSummary' => $this->buildFilterSummary($validated),
        ]);
    }

    private function buildFilteredClassTestQuery(array $filters): Builder
    {
        return ClassTest::query()
            ->where('academic_year_id', (int) $filters['academic_year_id'])
            ->where('class_id', (int) $filters['class_id'])
            ->when(!empty($filters['term_id']), function (Builder $query) use ($filters) {
                $query->where('term_id', (int) $filters['term_id']);
            })
            ->when(!empty($filters['subject_id']), function (Builder $query) use ($filters) {
                $query->where('subject_id', (int) $filters['subject_id']);
            });
    }

    private function buildFilterSummary(array $filters): array
    {
        $academicYearName = AcademicYear::where('id', (int) $filters['academic_year_id'])->value('name');
        $termName = !empty($filters['term_id'])
            ? Term::where('id', (int) $filters['term_id'])->value('name')
            : null;
        $className = SchoolClass::where('id', (int) $filters['class_id'])->value('name');
        $subjectName = !empty($filters['subject_id'])
            ? Subject::where('id', (int) $filters['subject_id'])->value('name')
            : null;

        return [
            'academic_year' => $academicYearName ?? '-',
            'term' => $termName ?? 'All Terms',
            'class' => $className ?? '-',
            'subject' => $subjectName ?? 'All Subjects',
        ];
    }
}
