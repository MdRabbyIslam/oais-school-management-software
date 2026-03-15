<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassTestMarksRequest;
use App\Models\ClassTest;
use App\Models\ClassTestMark;
use App\Models\StudentEnrollment;
use App\Services\Exam\ClassTestMarkEntryService;

class ClassTestMarkEntryController extends Controller
{
    protected ClassTestMarkEntryService $markEntryService;

    public function __construct(ClassTestMarkEntryService $markEntryService)
    {
        $this->markEntryService = $markEntryService;
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

        return view('pages.class_tests_marks_create', compact('classTest', 'enrollments', 'existingMarks'));
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

}
