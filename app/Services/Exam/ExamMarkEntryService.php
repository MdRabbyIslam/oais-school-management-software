<?php

namespace App\Services\Exam;

use App\Models\ExamAssessmentSubject;
use App\Models\ExamMark;
use App\Models\ExamMarkComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamMarkEntryService
{
    public function saveMarks(ExamAssessmentSubject $assessmentSubject, array $rows, int $enteredBy): void
    {
        DB::transaction(function () use ($assessmentSubject, $rows, $enteredBy) {
            $assessmentClass = $assessmentSubject->assessmentClass()->with('examAssessment')->firstOrFail();
            $components = $assessmentSubject->components()->orderBy('sort_order')->get()->values();

            $validEnrollmentIds = DB::table('student_enrollments')
                ->where('class_id', $assessmentClass->class_id)
                ->where('academic_year_id', $assessmentClass->examAssessment->academic_year_id)
                ->where('status', 'active')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($rows as $row) {
                $enrollmentId = (int) ($row['student_enrollment_id'] ?? 0);
                if (!in_array($enrollmentId, $validEnrollmentIds, true)) {
                    continue;
                }

                $isAbsent = (bool) ($row['is_absent'] ?? false);
                $inputMarks = $row['marks_obtained'] ?? null;
                $componentMarks = $row['component_marks'] ?? [];
                $calculatedMarks = $inputMarks;

                if ($components->isNotEmpty()) {
                    $calculatedMarks = (float) collect($componentMarks)
                        ->filter(fn ($mark) => $mark !== null && $mark !== '')
                        ->sum(fn ($mark) => (float) $mark);
                }

                if (!$isAbsent && $calculatedMarks !== null && (float) $calculatedMarks > (float) $assessmentSubject->total_marks) {
                    throw ValidationException::withMessages([
                        'rows' => "Marks cannot be greater than {$assessmentSubject->total_marks}.",
                    ]);
                }

                $examMark = ExamMark::updateOrCreate(
                    [
                        'assessment_subject_id' => $assessmentSubject->id,
                        'student_enrollment_id' => $enrollmentId,
                    ],
                    [
                        'marks_obtained' => $isAbsent ? null : $calculatedMarks,
                        'is_absent' => $isAbsent,
                        'entered_by' => $enteredBy,
                        'entered_at' => now(),
                    ]
                );

                if ($components->isNotEmpty()) {
                    foreach ($components as $index => $component) {
                        $componentMark = $componentMarks[$index] ?? null;
                        $componentMark = ($componentMark === '' || $componentMark === null) ? null : (float) $componentMark;

                        if ($componentMark !== null && $componentMark > (float) $component->total_marks) {
                            throw ValidationException::withMessages([
                                'rows' => "Component mark cannot exceed {$component->total_marks} for {$component->component_name}.",
                            ]);
                        }

                        ExamMarkComponent::updateOrCreate(
                            [
                                'exam_mark_id' => $examMark->id,
                                'assessment_subject_component_id' => $component->id,
                            ],
                            [
                                'marks_obtained' => $isAbsent ? null : $componentMark,
                                'is_absent' => $isAbsent,
                            ]
                        );
                    }
                }
            }
        });
    }
}

