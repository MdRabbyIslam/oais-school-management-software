<?php

// app/Http/Controllers/FeeAssignmentController.php
namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\FeeAssignment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeeAssignmentController extends Controller
{

    public function index(Request $request)
    {


        $allowedSorts = ['student_id','fee_id','term_id','status'];
        $sortBy  = $request->get('sort_by', 'due_date');
        $sortDir = $request->get('sort_dir', 'desc');

        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'due_date';
        }
        if (! in_array($sortDir, ['asc','desc'])) {
            $sortDir = 'desc';
        }


        // Base query, eager-loading relationships (include enrollment -> academic year)
        $query = FeeAssignment::with(['studentEnrollment.schoolClass', 'studentEnrollment.section','student.schoolClass', 'student.section', 'fee', 'term', 'studentEnrollment.academicYear']);

        // Filters
        if ($request->filled('class_id')) {
            $query->whereHas('student.section', fn($q) => $q->where('class_id', $request->class_id));
        }
        if ($request->filled('section_id')) {
            $query->whereHas('student', fn($q) => $q->where('section_id', $request->section_id));
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('fee_id')) {
            $query->where('fee_id', $request->fee_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                })->orWhereHas('fee', function ($sub) use ($search) {
                    $sub->where('fee_name', 'like', "%{$search}%");
                });
            });
        }
        if ($request->filled('term')) {
            $query->where('term_id', $request->term);
        }

        $query->orderBy($sortBy, $sortDir);


        $assignments = $query->paginate(20)->withQueryString();

        // Lookup data for filters
        $classes   = SchoolClass::all();
        $sections  = Section::with('schoolClass')->get();
        $students  = Student::select('id','name','student_id')->orderBy('name')->get();
        $statuses  = FeeAssignment::select('status')->distinct()->pluck('status');
        $fees      = Fee::all();

        return view('pages.fee-assignments.index', compact(
            'assignments','classes','sections','students','statuses','sortBy','sortDir','fees'
        ));
    }


    public function create()
    {
        // $academicYears that has enrollments only that academic year
        $academicYears = \App\Models\AcademicYear::whereHas('enrollments')->orderByDesc('start_date')->get();

        return view('pages.fee-assignments.create', [
            'students' => Student::with(['schoolClass', 'section', 'enrollments.academicYear'])->get(),
            'classes' => SchoolClass::all(),
            'sections' => Section::with('schoolClass')->get(),
            'fees' => Fee::with('classFeeAmounts')->get(),
            'terms' => Term::get(),
            'academicYears' => $academicYears
        ]);

    }

    public function getStudentsByYear(Request $request)
    {
        $academicYearId = $request->academic_year_id;

        // Fetch students who have an active enrollment in the selected academic year
        $students = Student::whereHas('enrollments', function($q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId)
            ->where('status', 'active');
        })->with(['schoolClass', 'section'])->get();

        return response()->json($students->map(function($s) {
            return [
                'id' => $s->id,
                'text' => "{$s->student_id} – {$s->name} ({$s->schoolClass->name} - {$s->section->section_name})"
            ];
        }));
    }



    public function preview(Request $request)
    {
        $academicYearId = $request->academic_year_id;
        $query = $this->getStudentsQuery($request)->whereHas('enrollments', fn($q) => $q->where('academic_year_id', $academicYearId));


        return response()->json([
            'count' => $query->count(),
            'sample' => $query->limit(5)
                    ->get()
                    ->map(function($student) {
                        return [
                            'name' => $student->name,
                            'class' => $student->schoolClass->name,
                            'section' => $student->section->name
                        ];
                    })
        ]);
    }

    // public function bulkStore(Request $request)
    // {

    //     // dd($request->all());

    //    try {
    //     $validated = $request->validate([
    //         'scope' => 'required|in:student,class,section,all',
    //         'student_id' => 'nullable|required_if:scope,student|exists:students,id',
    //         'class_id' => 'nullable|required_if:scope,class|exists:classes,id',
    //         'section_id' => 'nullable|required_if:scope,section|exists:sections,id',
    //         'fee_id' => 'required|exists:fees,id',
    //         'term_id' => 'nullable|required_if:fee_type,term-based|exists:terms,id',
    //         'amount_type' => 'required|in:class_default,custom',
    //         'custom_amount' => 'required_if:amount_type,custom|numeric|nullable|min:0',
    //         'due_date' => 'required|date',
    //         'start_date' => 'nullable|date',
    //         'end_date' => 'nullable|date|after:start_date'
    //     ]);

    //     $students = $this->getStudentsQuery($request)->get();

    //     $fee = Fee::with('classFeeAmounts')->where('id', $request->fee_id)->firstOrFail();

    //     if($fee->billing_type == 'term-based') {
    //        // ensure term_id is provided
    //         if (!$request->term_id) {
    //             return back()->withErrors(['message'=>'Term ID is required for term-based fees.'])
    //                 ->withInput();
    //         }
    //     }


    //     // dd($fee->classFeeAmounts());

    //     DB::transaction(function() use ($students, $fee, $request) {

    //         foreach ($students as $student) {
    //             $amount = $request->amount_type === 'custom'
    //                 ? $request->custom_amount
    //                 : $fee->classFeeAmounts()
    //                     ->where('class_id', $student->section->class_id)
    //                     ->first()->amount ?? 0;

    //             FeeAssignment::updateOrCreate(
    //                 [
    //                     'student_id' => $student->id,
    //                     'fee_id' => $fee->id,
    //                     'term_id' => $request->term_id
    //                 ],
    //                 [
    //                     'amount' => $amount,
    //                     'due_date' => $request->due_date,
    //                     'start_date' => $request->start_date,
    //                     'end_date' => $request->end_date,
    //                     'status' => 'active',
    //                     'meta' => [
    //                         'assigned_in_bulk' => true,
    //                         'amount_source' => $request->amount_type
    //                     ]
    //                 ]
    //             );
    //         }
    //     });

    //     return redirect()->route('fee-assignments.index')
    //         ->with('success', "Fee assigned to {$students->count()} students");
    //    }catch(\Throwable $e) {

    //         return back()->withErrors(['error'=> $e->getMessage()])
    //             ->withInput();
    //     }
    // }

    public function bulkStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'scope' => 'required|in:student,class,section,all',
                'student_id' => 'nullable|required_if:scope,student|exists:students,id',
                'class_id' => 'nullable|required_if:scope,class|exists:classes,id',
                'section_id' => 'nullable|required_if:scope,section|exists:sections,id',
                'fee_id' => 'required|exists:fees,id',
                'term_id' => 'nullable|required_if:fee_type,term-based|exists:terms,id',
                'amount_type' => 'required|in:class_default,custom',
                'custom_amount' => 'required_if:amount_type,custom|numeric|nullable|min:0',
                'due_date' => 'required|date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date'
            ]);

            $academicYearId = $request->academic_year_id;

            $students = $this->getStudentsQuery($request)
                ->whereHas('enrollments', fn($q) => $q->where('academic_year_id', $academicYearId))
                ->get();

            $fee = Fee::with('classFeeAmounts')->findOrFail($request->fee_id);

            if ($fee->billing_type === 'term-based' && !$request->term_id) {
                return back()->withErrors(['message' => 'Term ID is required for term-based fees.'])
                            ->withInput();
            }

            $duplicates = [];
            $inserted = 0;

            DB::transaction(function () use ($students, $fee, $academicYearId, $request, &$duplicates, &$inserted) {
                foreach ($students as $student) {

                    // Find the specific enrollment for the selected academic year
                    $enrollment = $student->enrollments()
                        ->where('academic_year_id', $academicYearId)
                        ->where('status', 'active')
                        ->first();

                    if (!$enrollment) continue;

                    $alreadyAssigned = FeeAssignment::where('student_id', $student->id)
                        ->where('fee_id', $fee->id)
                        ->where('term_id', $request->term_id)
                        ->where('student_enrollment_id', $enrollment->id)
                        ->exists();

                    if ($alreadyAssigned) {
                        $duplicates[] = $student->name ?? "ID {$student->id}";
                        continue;
                    }

                    $amount = $request->amount_type === 'custom'
                        ? $request->custom_amount
                        : ($fee->classFeeAmounts()
                            ->where('class_id', $student->section->class_id)
                            ->first()->amount ?? 0);

                    $payload = [
                        'student_id' => $student->id,
                        'student_enrollment_id' => $enrollment->id,
                        'fee_id' => $fee->id,
                        'term_id' => $request->term_id,
                        'amount' => $amount,
                        'due_date' => $request->due_date,
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                        'status' => 'active',
                        'meta' => [
                            'assigned_in_bulk' => true,
                            'amount_source' => $request->amount_type,
                            'academic_year_id' => $request->academic_year_id ?? null,
                        ]
                    ];



                    FeeAssignment::create($payload);

                    $inserted++;
                }
            });

            if (!empty($duplicates)) {
                return redirect()->back()
                    ->withErrors([
                        'error' => 'Skipped existing assignments for: ' . implode(', ', $duplicates)
                    ])
                    ->withInput();
            }

            return redirect()->route('fee-assignments.index')
                ->with('success', "Fee assigned to {$inserted} students");

        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])
                        ->withInput();
        }
    }


    protected function getStudentsQuery(Request $request)
    {
        // dd($request->all());

        // $query = Student::with(['class', 'section'])->active();
        $query = Student::with(['schoolClass', 'section','activeEnrollment']);

        switch ($request->scope) {
            case 'student':
                $query->where('id', $request->student_id);
                break;
            case 'class':
                $query->whereHas('section', function($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                });                break;
            case 'section':
                $query->where('section_id', $request->section_id);
                break;
            case 'all':
                // No additional filters needed
                break;
        }

        return $query;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:student,class,section',
            'student_id' => 'required_if:scope,student|exists:students,id',
            'class_id' => 'required_if:scope,class|exists:classes,id',
            'section_id' => 'required_if:scope,section|exists:sections,id',
            'fee_id' => 'required|exists:fees,id',
            'term_id' => 'nullable|required_if:fee_type,term-based|exists:terms,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date'
        ]);

        // Handle different assignment scopes
        switch ($request->scope) {
            case 'class':
                $students = Student::where('class_id', $request->class_id)->get();
                break;
            case 'section':
                $students = Student::where('section_id', $request->section_id)->get();
                break;
            default:
                $students = collect([Student::find($request->student_id)]);
        }

        // Create assignments
        foreach ($students as $student) {
            // Try to link to enrollment if academic_year_id provided or enrollment_id present
            $studentEnrollmentId = null;
            if ($request->filled('enrollment_id') && $request->scope === 'student') {
                $studentEnrollmentId = $request->enrollment_id;
            } elseif ($request->filled('academic_year_id')) {
                $en = \App\Models\StudentEnrollment::where('student_id', $student->id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->first();
                if ($en) $studentEnrollmentId = $en->id;
            }

            $payload = [
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'status' => 'active'
            ];
            if ($studentEnrollmentId) {
                $payload['student_enrollment_id'] = $studentEnrollmentId;
            }

            FeeAssignment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'fee_id' => $request->fee_id,
                    'term_id' => $request->term_id
                ],
                $payload
            );
        }

        return redirect()->route('fee-assignments.index')
            ->with('success', "Fee assigned to {$students->count()} students");
    }





    public function edit(FeeAssignment $feeAssignment)
    {
        $assignment = $feeAssignment->load(['student.section.schoolClass', 'fee']);
        $studentEnrollments = $assignment->student->enrollments()->with('academicYear')->orderByDesc('enrollment_date')->get();

        return view('pages.fee-assignments.edit', [
            'assignment' => $assignment,
            'terms' => Term::upcoming()->get(),
            'studentEnrollments' => $studentEnrollments,
            'academicYears' => \App\Models\AcademicYear::orderByDesc('start_date')->get()
        ]);
    }

    public function update(Request $request, FeeAssignment $feeAssignment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'term_id' => 'nullable|required_if:fee_type,term-based|exists:terms,id',
            'status' => 'required|in:active,completed,cancelled',
            'notes' => 'nullable|string'
        ]);

        // Prevent modifying historical records
        if ($feeAssignment->created_at < now()->subMonths(3)) {
            return back()->with('error', 'Cannot modify assignments older than 3 months');
        }

        $feeAssignment->update($validated + [
            'meta->last_updated_by' => auth()->id(),
            'meta->update_reason' => $request->update_reason
        ]);

        return redirect()->route('fee-assignments.index')
            ->with('success', 'Assignment updated successfully');
    }


    public function destroy(FeeAssignment $feeAssignment)
    {
        // Prevent deletion if payment exists
        // if ($feeAssignment->payments()->exists()) {
        //     return back()->with('error',
        //         'Cannot delete assignment with payment history. Mark as cancelled instead.');
        // }

        // Soft delete with audit trail
        $feeAssignment->update([
            'meta->deleted_by' => Auth::id(),
            'meta->deletion_reason' => request('deletion_reason')
        ]);

        $feeAssignment->delete();

        return back()->with('success', 'Assignment removed');
    }


    public function cancel(FeeAssignment $assignment)
    {
        $assignment->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'change_reason' => request('reason')
        ]);

        return back()->with('success', 'Assignment cancelled');
    }

    public function print(Request $request)
    {
        $allowedSorts = ['student_id','fee_id','term_id','status'];
        $sortBy  = $request->get('sort_by', 'due_date');
        $sortDir = $request->get('sort_dir', 'desc');

        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'due_date';
        }
        if (! in_array($sortDir, ['asc','desc'])) {
            $sortDir = 'desc';
        }

        $query = FeeAssignment::with(['student.schoolClass', 'student.section', 'fee', 'term']);

        if ($request->filled('class_id')) {
            $query->whereHas('student.section', fn($q) => $q->where('class_id', $request->class_id));
        }
        if ($request->filled('section_id')) {
            $query->whereHas('student', fn($q) => $q->where('section_id', $request->section_id));
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('fee_id')) {
            $query->where('fee_id', $request->fee_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('term')) {
            $query->where('term_id', $request->term);
        }

        $query->orderBy($sortBy, $sortDir);

        $assignments = $query->get();

        // Lookup data for filters
        $classes   = SchoolClass::all();
        $sections  = Section::with('schoolClass')->get();
        $students  = Student::select('id','name','student_id')->orderBy('name')->get();
        $statuses  = FeeAssignment::select('status')->distinct()->pluck('status');
        $fees      = Fee::all();

        // For heading: get filter values
        $filterValues = [
            'class'    => $classes->where('id', $request->class_id)->first()?->name ?? 'All',
            'section'  => $sections->where('id', $request->section_id)->first()?->section_name ?? 'All',
            'student'  => $students->where('id', $request->student_id)->first()?->name ?? 'All',
            'fee'      => $fees->where('id', $request->fee_id)->first()?->fee_name ?? 'All',
            'status'   => $request->status ?? 'All',
            'term'     => $request->term ? (\App\Models\Term::find($request->term)?->name ?? 'N/A') : 'All',
        ];

        return view('pages.fee-assignments.print', compact(
            'assignments','classes','sections','students','statuses','sortBy','sortDir','fees','filterValues'
        ));
    }
}
