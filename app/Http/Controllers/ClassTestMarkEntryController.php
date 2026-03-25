<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassTestMarksRequest;
use App\Models\ClassTest;
use App\Models\ClassTestMark;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\StudentEnrollment;
use App\Services\Exam\ClassTestMarkEntryService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class ClassTestMarkEntryController extends Controller
{
    protected ClassTestMarkEntryService $markEntryService;

    public function __construct(ClassTestMarkEntryService $markEntryService)
    {
        $this->markEntryService = $markEntryService;
    }

    public function bulkIndex(Request $request)
    {
        $this->authorize('manage-exams');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();

        $selectedAcademicYearId = $request->integer('academic_year_id');
        $selectedTermId = $request->integer('term_id');
        $selectedClassId = $request->integer('class_id');
        $selectedClassTestId = $request->integer('class_test_id');
        $action = (string) $request->input('action', 'filter');

        $testOptions = $this->buildBulkTestOptions($selectedAcademicYearId, $selectedTermId, $selectedClassId);

        if ($action === 'go' && $selectedClassTestId > 0) {
            return redirect()->route('class-tests.marks.bulk.create', $selectedClassTestId);
        }

        if ($action === 'go' && $selectedClassTestId <= 0) {
            return back()->with('error', 'Please select a class test event.');
        }

        return view('pages.class_tests_marks_bulk_index', compact(
            'academicYears',
            'terms',
            'classes',
            'testOptions'
        ));
    }

    public function bulkEvents(Request $request)
    {
        $this->authorize('manage-exams');

        $academicYearId = $request->integer('academic_year_id');
        $termId = $request->integer('term_id');
        $classId = $request->integer('class_id');

        $testOptions = $this->buildBulkTestOptions($academicYearId, $termId, $classId);

        return response()->json([
            'events' => $testOptions->map(function ($test) {
                return [
                    'id' => (int) $test->id,
                    'name' => (string) ($test->name ?? 'Class Test'),
                    'test_date' => $test->test_date,
                    'subjects_count' => (int) $test->subjects_count,
                ];
            })->values(),
        ]);
    }

    public function create(ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $classTest->load(['academicYear', 'term', 'schoolClass', 'subject']);

        $enrollments = StudentEnrollment::with('student')
            ->where('class_id', $classTest->class_id)
            ->where('academic_year_id', $classTest->academic_year_id)
            ->where('status', 'active')
            ->orderByRaw('ISNULL(roll_number), roll_number ASC')
            ->get();

        $existingMarks = ClassTestMark::where('class_test_id', $classTest->id)
            ->get()
            ->keyBy('student_enrollment_id');

        $relatedClassTests = $this->relatedClassTests($classTest);

        return view('pages.class_tests_marks_create', compact('classTest', 'enrollments', 'existingMarks', 'relatedClassTests'));
    }

    public function store(StoreClassTestMarksRequest $request, ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        if ($classTest->status === 'locked') {
            return back()->with('error', 'Class test is locked. Marks cannot be modified.');
        }

        $this->markEntryService->saveMarks(
            $classTest,
            $request->input('rows', []),
            (int) auth()->id()
        );

        // Any marks update requires republish review if it was already published.
        if ($classTest->status === 'published') {
            $classTest->update([
                'status' => 'draft',
                'published_by' => null,
                'published_at' => null,
            ]);

            return redirect()
                ->route('class-tests.marks.create', $classTest)
                ->with('success', 'Marks saved successfully. Status changed to Draft; publish again after review.');
        }

        return redirect()
            ->route('class-tests.marks.create', $classTest)
            ->with('success', 'Marks saved successfully.');
    }

    public function createBulk(ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $classTest->load(['academicYear', 'term', 'schoolClass', 'subject']);
        $relatedClassTests = $this->relatedClassTests($classTest);

        $enrollments = StudentEnrollment::with('student')
            ->where('class_id', $classTest->class_id)
            ->where('academic_year_id', $classTest->academic_year_id)
            ->where('status', 'active')
            ->orderByRaw('ISNULL(roll_number), roll_number ASC')
            ->get();

        $existingMarksByTest = ClassTestMark::query()
            ->whereIn('class_test_id', $relatedClassTests->pluck('id'))
            ->get()
            ->groupBy('class_test_id')
            ->map(fn ($marks) => $marks->keyBy('student_enrollment_id'));

        return view('pages.class_tests_marks_bulk_create', compact(
            'classTest',
            'relatedClassTests',
            'enrollments',
            'existingMarksByTest'
        ));
    }

    public function storeBulk(ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $relatedClassTests = $this->relatedClassTests($classTest);
        if ($relatedClassTests->isEmpty()) {
            return back()->with('error', 'No related class tests found for bulk marks entry.');
        }

        request()->validate([
            'rows' => ['required', 'array'],
            'rows.*.student_enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'rows.*.marks' => ['nullable', 'array'],
            'rows.*.marks.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rows = request()->input('rows', []);
        $existingMarksByTest = ClassTestMark::query()
            ->whereIn('class_test_id', $relatedClassTests->pluck('id'))
            ->get()
            ->groupBy('class_test_id')
            ->map(fn ($marks) => $marks->keyBy('student_enrollment_id'));
        $enteredBy = (int) auth()->id();
        $updatedTestsCount = 0;
        $republishRequiredCount = 0;
        $lockedTestsCount = 0;

        foreach ($relatedClassTests as $test) {
            if ($test->status === 'locked') {
                $lockedTestsCount++;
                continue;
            }

            $rowsForTest = collect($rows)->map(function ($row) use ($test, $existingMarksByTest) {
                $existingMark = optional($existingMarksByTest->get($test->id))->get((int) $row['student_enrollment_id']);

                return [
                    'student_enrollment_id' => $row['student_enrollment_id'],
                    'marks_obtained' => $row['marks'][$test->id] ?? null,
                    'is_absent' => (bool) ($existingMark->is_absent ?? false),
                    'remarks' => $existingMark->remarks ?? null,
                ];
            })->all();

            $this->markEntryService->saveMarks($test, $rowsForTest, $enteredBy);
            $updatedTestsCount++;

            if ($test->status === 'published') {
                $test->update([
                    'status' => 'draft',
                    'published_by' => null,
                    'published_at' => null,
                ]);
                $republishRequiredCount++;
            }
        }

        if ($updatedTestsCount === 0) {
            return redirect()
                ->route('class-tests.marks.bulk.create', $classTest)
                ->with('error', 'All related class tests are locked. No marks were updated.');
        }

        $message = "Bulk marks saved for {$updatedTestsCount} subject test(s).";
        if ($republishRequiredCount > 0) {
            $message .= " {$republishRequiredCount} published test(s) moved to Draft; publish again after review.";
        }
        if ($lockedTestsCount > 0) {
            $message .= " Skipped {$lockedTestsCount} locked test(s).";
        }

        return redirect()
            ->route('class-tests.marks.bulk.create', $classTest)
            ->with('success', $message);
    }

    public function print(ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $classTest->load(['academicYear', 'term', 'schoolClass', 'subject']);

        $enrollments = StudentEnrollment::with('student')
            ->where('class_id', $classTest->class_id)
            ->where('academic_year_id', $classTest->academic_year_id)
            ->where('status', 'active')
            ->orderByRaw('ISNULL(roll_number), roll_number ASC')
            ->get();

        $marksByEnrollment = ClassTestMark::where('class_test_id', $classTest->id)
            ->get()
            ->keyBy('student_enrollment_id');

        $rows = $enrollments->map(function ($enrollment) use ($marksByEnrollment, $classTest) {
            $mark = $marksByEnrollment->get($enrollment->id);
            $obtained = $mark && !$mark->is_absent ? (float) $mark->marks_obtained : null;
            $passMarks = $classTest->pass_marks !== null ? (float) $classTest->pass_marks : null;

            $status = 'N/A';
            if ($mark && $mark->is_absent) {
                $status = 'ABSENT';
            } elseif ($obtained !== null) {
                $status = $passMarks === null || $obtained >= $passMarks ? 'PASS' : 'FAIL';
            }

            return [
                'roll_number' => $enrollment->roll_number,
                'student_name' => $enrollment->student->name ?? ('Student #' . $enrollment->student_id),
                'obtained' => $obtained,
                'is_absent' => (bool) optional($mark)->is_absent,
                'remarks' => optional($mark)->remarks,
                'status' => $status,
            ];
        })->values();

        return view('pages.class_tests_print', compact('classTest', 'rows'));
    }

    public function printBlank(ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $classTest->load(['academicYear', 'term', 'schoolClass', 'subject']);

        $enrollments = StudentEnrollment::with('student')
            ->where('class_id', $classTest->class_id)
            ->where('academic_year_id', $classTest->academic_year_id)
            ->where('status', 'active')
            ->orderByRaw('ISNULL(roll_number), roll_number ASC')
            ->get();

        $rows = $enrollments->map(function ($enrollment) {
            return [
                'roll_number' => $enrollment->roll_number,
                'student_name' => $enrollment->student->name ?? ('Student #' . $enrollment->student_id),
            ];
        })->values();

        return view('pages.class_tests_blank_print', compact('classTest', 'rows'));
    }

    private function relatedClassTests(ClassTest $classTest): Collection
    {
        $query = ClassTest::query()
            ->with('subject')
            ->where('academic_year_id', $classTest->academic_year_id)
            ->where('term_id', $classTest->term_id)
            ->where('class_id', $classTest->class_id)
            ->where('name', $classTest->name)
            ->orderBy('subject_id');

        if ($classTest->test_date) {
            $query->whereDate('test_date', $classTest->test_date->format('Y-m-d'));
        } else {
            $query->whereNull('test_date');
        }

        return $query->get();
    }

    private function buildBulkTestOptions(int $academicYearId, int $termId, int $classId): Collection
    {
        $query = ClassTest::query()
            ->selectRaw('MIN(id) as id, academic_year_id, term_id, class_id, name, test_date, COUNT(*) as subjects_count')
            ->groupBy('academic_year_id', 'term_id', 'class_id', 'name', 'test_date')
            ->orderByRaw('ISNULL(test_date), test_date DESC')
            ->orderByDesc('id');

        if ($academicYearId > 0) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($termId > 0) {
            $query->where('term_id', $termId);
        }

        if ($classId > 0) {
            $query->where('class_id', $classId);
        }

        return $query->get();
    }
}
