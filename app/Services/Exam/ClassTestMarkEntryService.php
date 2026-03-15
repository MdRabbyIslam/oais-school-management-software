<?php

namespace App\Services\Exam;

use App\Models\ClassTest;
use App\Models\ClassTestMark;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassTestMarkEntryService
{
    public function saveMarks(ClassTest $classTest, array $rows, int $enteredBy): void
    {
        DB::transaction(function () use ($classTest, $rows, $enteredBy) {
            $validEnrollmentIds = DB::table('student_enrollments')
                ->where('class_id', $classTest->class_id)
                ->where('academic_year_id', $classTest->academic_year_id)
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
                $marks = $row['marks_obtained'] ?? null;
                $remarks = $row['remarks'] ?? null;
                $marks = ($marks === '' || $marks === null) ? null : (float) $marks;

                if (!$isAbsent && $marks !== null && $marks > (float) $classTest->total_marks) {
                    throw ValidationException::withMessages([
                        'rows' => "Marks cannot be greater than {$classTest->total_marks}.",
                    ]);
                }

                ClassTestMark::updateOrCreate(
                    [
                        'class_test_id' => $classTest->id,
                        'student_enrollment_id' => $enrollmentId,
                    ],
                    [
                        'marks_obtained' => $isAbsent ? null : $marks,
                        'is_absent' => $isAbsent,
                        'remarks' => $remarks,
                        'entered_by' => $enteredBy,
                        'entered_at' => now(),
                    ]
                );
            }
        });
    }
}
