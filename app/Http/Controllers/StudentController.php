<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\FeeAssignment;
use App\Models\FeeGroup;
use App\Models\Student;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\SchoolClass;
use App\Models\Term;

class StudentController extends Controller
{


    public function index(Request $request)
    {
        $this->authorize('manage-students');

        $classes  = SchoolClass::with('sections')->get();
        $sections = Section::select('id','class_id','section_name')->get();

        $query = Student::with(['section.schoolClass', 'activeEnrollment.academicYear']);

        // class filter
        if ($request->filled('class_id')) {
            $query->whereHas('section.schoolClass', fn($q) =>
                $q->where('id', $request->class_id)
            );
        }

        // section filter
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        // search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $students = $query
                        ->paginate(100)    // 100 per page
                        ->withQueryString();

        return view('pages.students.index', compact('students','classes','sections'));
    }



    public function create()
    {
        $this->authorize('manage-students');
        $sections = Section::with('schoolClass')->get();
        $feeGroups = FeeGroup::with('fees')->get(); // Get all fee groups with their associated fees

        return view('pages.students.create', compact('sections', 'feeGroups'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage-students');

        $request->validate([
            'name' => 'required|string|max:255',
            'dob' => 'required|date',
            'primary_guardian_name' => 'required|string|max:255',
            'primary_guardian_contact' => 'required|string|max:20',
            'primary_guardian_relation' => 'required|string|max:50',
            'section_id' => 'required|exists:sections,id',
            'admission_date' => 'required|date',
            'roll_number' => 'nullable|integer',
            'birth_certificate_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'marksheet_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            // 'fee_ids' => 'required|array', // Ensure fee assignments are selected
        ]);

        $data = $request->only([
            'name', 'dob', 'primary_guardian_name', 'primary_guardian_contact', 'primary_guardian_relation',
            'secondary_guardian_name', 'secondary_guardian_contact', 'secondary_guardian_relation',
            'address', 'admission_date', 'roll_number', 'section_id'
        ]);

        if ($request->hasFile('birth_certificate_path')) {
            $data['birth_certificate_path'] = $request->file('birth_certificate_path')->store('documents/birth_certificates', 'public_upload');
        }

        if ($request->hasFile('marksheet_path')) {
            $data['marksheet_path'] = $request->file('marksheet_path')->store('documents/marksheets', 'public_upload');
        }

        // Create student
        $student = Student::create($data);

        // Get the current date for the due_date (or set a default due date)
        $dueDate = now(); // You can set a fixed date or use `now()` for current date

        // Assign fees to the student
        // foreach ($request->fee_ids as $feeId) {

        //     // add the fees to the student through fee_assignment table
        //     $student->fees()->attach($feeId, [
        //         'due_date' => $dueDate,
        //         'amount' => Fee::find($feeId)->amount, // Assuming you have an amount field in the Fee model
        //         'status' => 'active', // Set default status
        //     ]);
        // }

        return redirect()->route('students.index')->with('success', 'Student created and fees assigned successfully!');
    }

    public function edit(Student $student)
    {
        $this->authorize('manage-students');
        $sections = Section::with('schoolClass')->get();
        $feeGroups = FeeGroup::with('fees')->get(); // Get all fee groups with their associated fees
        $assignedFees = $student->fees->pluck('id')->toArray(); // Get the IDs of assigned fees
        return view('pages.students.edit', compact('student', 'sections', 'feeGroups', 'assignedFees'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorize('manage-students');

        $request->validate([
            'name' => 'required|string|max:255',
            'dob' => 'required|date',
            'blood_group' => 'nullable|string|max:10',
            'primary_guardian_name' => 'required|string|max:255',
            'primary_guardian_contact' => 'required|string|max:20',
            'primary_guardian_relation' => 'required|string|max:50',
            // 'section_id' => 'required|exists:sections,id',
            // 'admission_date' => 'required|date',
            // 'roll_number' => 'nullable|integer',
            // 'birth_certificate_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            // 'marksheet_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            // 'fee_ids' => 'required|array', // Ensure fee assignments are selected
        ]);

        $data = $request->except(['birth_certificate_path', 'marksheet_path']);

        if ($request->hasFile('birth_certificate_path')) {
            $data['birth_certificate_path'] = $request->file('birth_certificate_path')->store('documents/birth_certificates', 'public_upload');
        }

        if ($request->hasFile('marksheet_path')) {
            $data['marksheet_path'] = $request->file('marksheet_path')->store('documents/marksheets', 'public_upload');
        }

        // Update student data
        $student->update($data);

        // Update fee assignments
        // $student->fees()->sync($request->fee_ids); // Sync fees to ensure that old fees are removed and new ones are assigned

        return redirect()->route('students.index')->with('success', 'Student information updated successfully!');
    }


    public function destroy(Student $student)
    {
        $this->authorize('manage-students');
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Student deleted.');
    }

    // in StudentController.php
    public function ajaxSelect2(Request $req)
    {
        // If an explicit id is passed, return that single option (for preselect)
        if ($req->filled('id')) {
            $s = Student::with('section.schoolClass')
                ->select('id','student_id','name','section_id')
                ->find($req->input('id'));

            if (! $s) {
                return response()->json(['result' => null]);
            }

            $text = "{$s->student_id} – {$s->name}"
                . ($s->section
                    ? " ({$s->section->schoolClass->name} – {$s->section->section_name})"
                    : '');

            return response()->json([
                'result' => ['id' => $s->id, 'text' => $text],
            ]);
        }

        // Normal search flow (unchanged logic)
        $term = $req->input('q', '');
        $students = Student::with('section.schoolClass')
            ->where(function($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                ->orWhere('student_id', 'like', "%{$term}%");
            })
            ->orWhereHas('section.schoolClass', fn($q2) =>
                $q2->where('name', 'like', "%{$term}%")
            )
            ->limit(30)
            ->get();

        $results = $students->map(fn($s) => [
            'id'   => $s->id,
            'text' => "{$s->student_id} – {$s->name}"
                . ($s->section
                    ? " ({$s->section->schoolClass->name} – {$s->section->section_name})"
                    : ''),
        ]);

        return response()->json(['results' => $results]);
    }


    public function manageFees(Student $student)
    {
        $this->authorize('manage-students');
        $fees = Fee::with('feeGroup', 'classFeeAmounts')->get();
        $terms = Term::all();
        $assignments = $student->currentEnrollmentFeeAssignments()->with('fee', 'term')->get();

        $assigned = [];
        foreach ($assignments as $a) {
            $key = $a->fee->billing_type === 'term-based'
                ? $a->fee_id . '-' . ($a->term_id ?? '0')
                : $a->fee_id;
            $assigned[$key] = $a;
        }

        $unassigned = [];
        foreach ($fees as $fee) {
            $defaultAmount = $fee->classFeeAmounts
                ->where('class_id', $student->section->class_id)
                ->first()
                ->amount ?? '';
            if ($fee->billing_type === 'term-based') {
                foreach ($terms as $term) {
                    $key = $fee->id . '-' . $term->id;
                    if (!isset($assigned[$key])) {
                        $unassigned[] = [
                            'fee' => $fee,
                            'term' => $term,
                            'default_amount' => $defaultAmount,
                        ];
                    }
                }
            } else {
                $key = $fee->id;
                if (!isset($assigned[$key])) {
                    $unassigned[] = [
                        'fee' => $fee,
                        'term' => null,
                        'default_amount' => $defaultAmount,
                    ];
                }
            }
        }

        return view('pages.students.manage-fees', [
            'student' => $student,
            'assigned' => $assigned,
            'unassigned' => $unassigned,
            'terms' => $terms,
            'fees' => $fees,
        ]);
    }

    public function updateFees(Request $request, Student $student)
    {
        $this->authorize('manage-students');

        $activeEnrollmentId = optional($student->activeEnrollment()->first())->id;
        if (! $activeEnrollmentId) {
            return back()->withErrors(['msg' => 'No active enrollment found for this student.']);
        }

        // Assigned: Only process checked rows
        $assignedKeys = $request->input('edit_assigned_keys', []);
        $assignedData = [];
        foreach ($assignedKeys as $i => $key) {
            $assignedData[] = [
                'key'     => $key,
                'amount'  => $request->assigned_amounts[$i] ?? null,
                'due_date'=> $request->assigned_due_dates[$i] ?? null,
                'status'  => $request->assigned_statuses[$i] ?? null,
            ];
        }

        // Unassigned: Only process checked rows
        $assignKeys = $request->input('assign_keys', []);
        $assignData = [];
        foreach ($assignKeys as $i => $key) {
            $assignData[] = [
                'key'     => $key,
                'amount'  => $request->assign_amounts[$i] ?? null,
                'due_date'=> $request->assign_due_dates[$i] ?? null,
                'term_id' => $request->assign_term_ids[$i] ?? null,
            ];
        }

        // Validation for assigned
        foreach ($assignedData as $row) {
            if (
                !is_numeric($row['amount']) ||
                $row['amount'] < 0 ||
                !$row['due_date'] ||
                !in_array($row['status'], ['active', 'cancelled', 'completed'])
            ) {
                return back()->withErrors(['msg' => 'All fields are required for edited assigned fees.']);
            }
        }

        // Validation for unassigned
        foreach ($assignData as $row) {
            if (
                !is_numeric($row['amount']) ||
                $row['amount'] < 0 ||
                !$row['due_date'] ||
                ($row['term_id'] && !\App\Models\Term::find($row['term_id']))
            ) {
                return back()->withErrors(['msg' => 'All fields are required for newly assigned fees.']);
            }
        }

        // dd($assignedData);

        // Update assigned
        foreach ($assignedData as $row) {
            [$feeId, $termId] = array_pad(explode('-', $row['key']), 2, null);
            $query = $student->feeAssignments()
                ->where('student_enrollment_id', $activeEnrollmentId)
                ->where('fee_id', $feeId);
            if ($termId) $query->where('term_id', $termId);
            $assignment = $query->first();

            if ($assignment) {
                $assignment->update([
                    'amount'   => $row['amount'],
                    'due_date' => $row['due_date'],
                    'status'   => $row['status']
                ]);
            }
        }

        // Assign new
        foreach ($assignData as $row) {
            [$feeId, $termId] = array_pad(explode('-', $row['key']), 2, null);
            FeeAssignment::create([
                'student_id' => $student->id,
                'student_enrollment_id' => $activeEnrollmentId,
                'fee_id'     => $feeId,
                'term_id'    => $termId,
                'amount'     => $row['amount'],
                'due_date'   => $row['due_date'],
                'status'     => 'active'
            ]);
        }

        return redirect()->route('students.manage-fees', $student)->with('success', 'Fee assignments updated.');
    }


}
