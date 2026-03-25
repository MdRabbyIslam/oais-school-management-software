<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassTestRequest;
use App\Http\Requests\UpdateClassTestRequest;
use App\Models\AcademicYear;
use App\Models\ClassTest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Exam\ClassTestService;
use Illuminate\Http\Request;

class ClassTestController extends Controller
{
    protected ClassTestService $classTestService;

    public function __construct(ClassTestService $classTestService)
    {
        $this->classTestService = $classTestService;
    }

    public function index(Request $request)
    {
        $this->authorize('manage-exams');

        $query = ClassTest::with(['academicYear', 'term', 'schoolClass', 'subject'])
            ->withCount('marks');

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', (int) $request->input('academic_year_id'));
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', (int) $request->input('term_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', (int) $request->input('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $classTests = $query->latest()->paginate(20)->appends($request->query());

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();

        return view('pages.class_tests_index', compact('classTests', 'academicYears', 'terms', 'classes'));
    }

    public function create()
    {
        $this->authorize('manage-exams');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();
        $subjects = Subject::with(['classes:id'])->orderBy('name')->get();

        return view('pages.class_tests_create', compact('academicYears', 'terms', 'classes', 'subjects'));
    }

    public function store(StoreClassTestRequest $request)
    {
        $this->authorize('manage-exams');

        $result = $this->classTestService->create($request->validated(), (int) auth()->id());

        $successMessage = $result['created_count'] > 1
            ? "Class tests created successfully for {$result['created_count']} subjects."
            : 'Class test created successfully.';

        return redirect()->route('class-tests.index')->with('success', $successMessage);
    }

    public function edit(ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();
        $subjects = Subject::with(['classes:id'])->orderBy('name')->get();

        return view('pages.class_tests_edit', compact('classTest', 'academicYears', 'terms', 'classes', 'subjects'));
    }

    public function update(UpdateClassTestRequest $request, ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $this->classTestService->update($classTest, $request->validated(), (int) auth()->id());

        return redirect()->route('class-tests.index')->with('success', 'Class test updated successfully.');
    }

    public function destroy(Request $request, ClassTest $classTest)
    {
        $this->authorize('manage-exams');

        $hasMarks = $classTest->marks()->exists();
        if ($hasMarks && !$request->boolean('force_delete_with_marks')) {
            return back()->with('error', 'This class test has marks. Confirm delete to proceed.');
        }

        $classTest->delete();

        return redirect()->route('class-tests.index')
            ->with('success', $hasMarks
                ? 'Class test deleted successfully (including existing marks).'
                : 'Class test deleted successfully.');
    }

    public function bulkUpdateStatus(Request $request)
    {
        $this->authorize('manage-exams');

        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['required', 'integer', 'exists:class_tests,id'],
            'status' => ['required', 'in:draft,published,locked'],
        ]);

        $status = (string) $validated['status'];
        $selectedIds = collect($validated['selected_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $classTests = ClassTest::whereIn('id', $selectedIds)->get();
        $updated = 0;

        foreach ($classTests as $classTest) {
            $payload = ['status' => $status];
            if ($status === 'published') {
                $payload['published_by'] = (int) auth()->id();
                $payload['published_at'] = now();
            } else {
                $payload['published_by'] = null;
                $payload['published_at'] = null;
            }

            $classTest->update($payload);
            $updated++;
        }

        return redirect()->route('class-tests.index')
            ->with('success', "Status updated for {$updated} class test(s).");
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorize('manage-exams');

        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['required', 'integer', 'exists:class_tests,id'],
            'force_delete_with_marks' => ['nullable', 'boolean'],
        ]);

        $selectedIds = collect($validated['selected_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $forceDeleteWithMarks = $request->boolean('force_delete_with_marks');
        $classTests = ClassTest::whereIn('id', $selectedIds)->withCount('marks')->get();

        $deleted = 0;
        $skippedWithMarks = 0;

        foreach ($classTests as $classTest) {
            if ($classTest->marks_count > 0 && !$forceDeleteWithMarks) {
                $skippedWithMarks++;
                continue;
            }

            $classTest->delete();
            $deleted++;
        }

        $message = "Deleted {$deleted} class test(s).";
        if ($skippedWithMarks > 0) {
            $message .= " Skipped {$skippedWithMarks} class test(s) because they have marks.";
        }

        return redirect()->route('class-tests.index')->with('success', $message);
    }
}
