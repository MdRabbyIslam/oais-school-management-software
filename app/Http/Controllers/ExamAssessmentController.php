<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamAssessmentRequest;
use App\Http\Requests\UpdateExamAssessmentRequest;
use App\Models\AcademicYear;
use App\Models\ExamAssessment;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\Exam\ExamAssessmentService;

class ExamAssessmentController extends Controller
{
    protected ExamAssessmentService $assessmentService;

    public function __construct(ExamAssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    public function index()
    {
        $this->authorize('manage-exams');
        $assessments = ExamAssessment::with(['academicYear', 'term', 'assessmentClasses.schoolClass'])
            ->latest()
            ->paginate(20);

        return view('pages.exams_assessments_index', compact('assessments'));
    }

    public function create()
    {
        $this->authorize('manage-exams');
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();

        return view('pages.exams_assessments_create', compact('academicYears', 'terms', 'classes'));
    }

    public function store(StoreExamAssessmentRequest $request)
    {
        $this->authorize('manage-exams');
        $result = $this->assessmentService->create($request->validated(), (int) auth()->id());
        $assessment = $result['assessment'];

        $successMessage = "Exam assessment created. Auto-initialized {$result['initialized_subjects']} subject setup(s) across {$result['processed_classes']} class(es).";

        $redirect = redirect()
            ->route('exam-assessment-classes.setup.edit', $assessment->assessmentClasses()->firstOrFail())
            ->with('success', $successMessage);

        if ($result['skipped_subjects_without_policy'] > 0) {
            $redirect->with('warning', "{$result['skipped_subjects_without_policy']} subject(s) were skipped because no active grading policy was found.");
        }

        return $redirect;
    }

    public function edit(ExamAssessment $examAssessment)
    {
        $this->authorize('manage-exams');
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $terms = Term::orderBy('start_date')->get();
        $classes = SchoolClass::orderBy('class_level')->get();
        $selectedClassIds = $examAssessment->assessmentClasses()->pluck('class_id')->all();

        return view('pages.exams_assessments_edit', compact('examAssessment', 'academicYears', 'terms', 'classes', 'selectedClassIds'));
    }

    public function update(UpdateExamAssessmentRequest $request, ExamAssessment $examAssessment)
    {
        $this->authorize('manage-exams');
        $result = $this->assessmentService->update($examAssessment, $request->validated());

        $successMessage = 'Exam assessment updated successfully.';
        if ($result['processed_classes'] > 0) {
            $successMessage .= " Auto-initialized {$result['initialized_subjects']} subject setup(s) for {$result['processed_classes']} newly added class(es).";
        }

        $redirect = redirect()->route('exam-assessments.index')
            ->with('success', $successMessage);

        if ($result['skipped_subjects_without_policy'] > 0) {
            $redirect->with('warning', "{$result['skipped_subjects_without_policy']} subject(s) were skipped because no active grading policy was found.");
        }

        return $redirect;
    }

    public function destroy(ExamAssessment $examAssessment)
    {
        $this->authorize('manage-exams');
        $hasMarks = $examAssessment->assessmentClasses()
            ->whereHas('assessmentSubjects.marks')
            ->exists();

        if ($hasMarks) {
            return back()->with('error', 'Cannot delete assessment because marks already exist.');
        }

        $examAssessment->delete();

        return redirect()->route('exam-assessments.index')
            ->with('success', 'Exam assessment deleted successfully.');
    }
}
