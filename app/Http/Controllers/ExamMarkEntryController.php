<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamMarksRequest;
use App\Models\ExamAssessmentClass;
use App\Models\ExamAssessmentSubject;
use App\Models\ExamMark;
use App\Models\StudentEnrollment;
use App\Services\Exam\ExamMarkEntryService;
use Illuminate\Http\Request;

class ExamMarkEntryController extends Controller
{
    protected ExamMarkEntryService $markEntryService;

    public function __construct(ExamMarkEntryService $markEntryService)
    {
        $this->markEntryService = $markEntryService;
    }

    public function create(Request $request, ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        $examAssessmentClass->load(['examAssessment', 'schoolClass']);
        $assessmentClasses = $examAssessmentClass->examAssessment
            ->assessmentClasses()
            ->with('schoolClass')
            ->get()
            ->sortBy(fn ($item) => (int) optional($item->schoolClass)->class_level)
            ->values();

        $assessmentSubjects = $examAssessmentClass->assessmentSubjects()
            ->with(['subject', 'components'])
            ->orderBy('subject_id')
            ->get();

        $selectedSubject = null;
        if ($assessmentSubjects->isNotEmpty()) {
            $selectedSubjectId = (int) $request->query('assessment_subject_id', $assessmentSubjects->first()->id);
            $selectedSubject = $assessmentSubjects->firstWhere('id', $selectedSubjectId) ?? $assessmentSubjects->first();
        }

        $enrollments = collect();
        $existingMarks = collect();
        if ($selectedSubject) {
            $enrollments = StudentEnrollment::with('student')
                ->where('class_id', $examAssessmentClass->class_id)
                ->where('academic_year_id', $examAssessmentClass->examAssessment->academic_year_id)
                ->where('status', 'active')
                ->orderByRaw('ISNULL(roll_number), roll_number ASC')
                ->get();

            $existingMarks = ExamMark::with('components')
                ->where('assessment_subject_id', $selectedSubject->id)
                ->get()
                ->keyBy('student_enrollment_id');
        }

        return view('pages.exams_marks_create', compact(
            'examAssessmentClass',
            'assessmentClasses',
            'assessmentSubjects',
            'selectedSubject',
            'enrollments',
            'existingMarks'
        ));
    }

    public function store(StoreExamMarksRequest $request, ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        if ($examAssessmentClass->examAssessment()->value('status') === 'locked') {
            return back()->with('error', 'Assessment is locked. Marks cannot be modified.');
        }

        $assessmentSubject = $examAssessmentClass->assessmentSubjects()
            ->with('components')
            ->findOrFail($request->integer('assessment_subject_id'));

        $this->markEntryService->saveMarks(
            $assessmentSubject,
            $request->input('rows', []),
            (int) auth()->id()
        );

        // Marks update changes final outcome, so published result must be recalculated.
        $examAssessmentClass->update([
            'is_published' => false,
            'published_by' => null,
            'published_at' => null,
        ]);

        return redirect()
            ->route('exam-assessment-classes.marks.create', [
                'examAssessmentClass' => $examAssessmentClass->id,
                'assessment_subject_id' => $assessmentSubject->id,
            ])
            ->with('success', 'Marks saved successfully. Results are now unpublished; publish again after final review.');
    }
}
