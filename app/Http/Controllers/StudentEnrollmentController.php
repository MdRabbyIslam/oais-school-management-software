<?php

namespace App\Http\Controllers;

use App\Models\GradingPolicy;
use App\Models\Student;
use App\Models\StudentEnrollment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentEnrollmentController extends Controller
{
/**
     * View all enrollments for a specific student.
     */
    public function view(Student $student)
    {
        // Load enrollments with their relations for the specific student
        $student->load(['enrollments.academicYear', 'enrollments.schoolClass', 'enrollments.section', 'enrollments.optionalSubject']);

        $fourthSubjectsByClass = GradingPolicy::query()
            ->with('subject')
            ->where('is_active', true)
            ->where('is_fourth_subject_eligible', true)
            ->whereIn('class_id', $student->enrollments->pluck('class_id')->unique()->values())
            ->get()
            ->groupBy('class_id');

        return view('pages.enrollments.view', compact('student', 'fourthSubjectsByClass'));
    }

    /**
     * Update roll numbers for selected enrollments.
     */
    public function editRollNumber(Request $request, Student $student)
    {
        $validated = $request->validate([
            'enrollments' => 'required|array',
            'enrollments.*.id' => 'required|exists:student_enrollments,id',
            'enrollments.*.roll_number' => 'required|string|max:50',
            'enrollments.*.optional_subject_id' => 'nullable|exists:subjects,id',
            'selected_ids' => 'required|array|min:1'
        ], [
            'selected_ids.required' => 'Please select at least one enrollment row to update.',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['enrollments'] as $index => $data) {
                // Only process if the checkbox for this ID was selected
                if (in_array($data['id'], $validated['selected_ids'])) {

                    // 1. Find the enrollment to get its context (Year, Class, Section)
                    $enrollment = StudentEnrollment::findOrFail($data['id']);

                    // 2. Check if this roll number is taken by ANOTHER student in the same context
                    $isTaken = StudentEnrollment::where('academic_year_id', $enrollment->academic_year_id)
                        ->where('class_id', $enrollment->class_id)
                        ->where('section_id', $enrollment->section_id)
                        ->where('roll_number', $data['roll_number'])
                        ->where('student_id', '!=', $student->id) // Exclude current student
                        ->exists();

                    if ($isTaken) {
                        throw new \Exception("Roll number '{$data['roll_number']}' is already assigned to another student in {$enrollment->schoolClass->name} ({$enrollment->section->section_name}).");
                    }

                    $optionalSubjectId = !empty($data['optional_subject_id']) ? (int) $data['optional_subject_id'] : null;
                    if ($optionalSubjectId !== null) {
                        $isAllowedFourthSubject = GradingPolicy::query()
                            ->where('class_id', $enrollment->class_id)
                            ->where('subject_id', $optionalSubjectId)
                            ->where('is_active', true)
                            ->where('is_fourth_subject_eligible', true)
                            ->exists();

                        if (!$isAllowedFourthSubject) {
                            throw new \Exception('Selected 4th subject is not allowed for ' . $enrollment->schoolClass->name . '.');
                        }
                    }

                    // 3. Perform update
                    $enrollment->update([
                        'roll_number' => $data['roll_number'],
                        'optional_subject_id' => $optionalSubjectId,
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Selected roll numbers updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Return back with a global error message
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
