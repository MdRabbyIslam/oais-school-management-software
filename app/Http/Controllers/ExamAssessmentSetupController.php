<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamAssessmentSubjectRequest;
use App\Models\ExamAssessmentClass;
use App\Models\ExamAssessmentSubject;
use App\Models\GradingPolicy;
use App\Services\Exam\ExamAssessmentSetupService;

class ExamAssessmentSetupController extends Controller
{
    protected ExamAssessmentSetupService $setupService;

    public function __construct(ExamAssessmentSetupService $setupService)
    {
        $this->setupService = $setupService;
    }

    public function edit(ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        $examAssessmentClass->load([
            'examAssessment.term',
            'examAssessment.academicYear',
            'schoolClass',
            'assessmentSubjects.subject',
            'assessmentSubjects.gradingPolicy.gradeScheme',
            'assessmentSubjects.components',
        ]);

        $subjects = $examAssessmentClass->schoolClass->subjects()->orderBy('name')->get();
        $policies = GradingPolicy::where('class_id', $examAssessmentClass->class_id)
            ->with(['gradeScheme', 'subject'])
            ->orderBy('subject_id')
            ->orderBy('total_marks')
            ->get();
        $policiesBySubject = $policies->keyBy('subject_id');
        $subjectsWithoutPolicy = $subjects->filter(fn ($subject) => !$policiesBySubject->has($subject->id))->values();

        $assessmentClasses = $examAssessmentClass->examAssessment
            ->assessmentClasses()
            ->with('schoolClass')
            ->get()
            ->sortBy(fn ($item) => (int) optional($item->schoolClass)->class_level)
            ->values();

        return view('pages.exams_setup_edit', compact(
            'examAssessmentClass',
            'subjects',
            'policiesBySubject',
            'subjectsWithoutPolicy',
            'assessmentClasses'
        ));
    }

    public function storeSubject(StoreExamAssessmentSubjectRequest $request, ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        $this->setupService->upsertSubject($examAssessmentClass, $request->validated());

        return back()->with('success', 'Exam subject setup saved successfully.');
    }

    public function destroySubject(ExamAssessmentSubject $examAssessmentSubject)
    {
        $this->authorize('manage-exams');
        $hasMarks = $examAssessmentSubject->marks()->exists();
        if ($hasMarks) {
            return back()->with('error', 'Cannot delete subject setup because marks already exist.');
        }

        $examAssessmentSubject->delete();

        return back()->with('success', 'Exam subject setup deleted successfully.');
    }
}
