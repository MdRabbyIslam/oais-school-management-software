<?php

namespace App\Http\Controllers;

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
        $student->load(['enrollments.academicYear', 'enrollments.schoolClass', 'enrollments.section']);

        return view('pages.enrollments.view', compact('student'));
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

                    // 3. Perform update
                    $enrollment->update(['roll_number' => $data['roll_number']]);
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
