<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{

    public function create(Request $request)
    {
        $this->authorize('manage-attendance');

        $sections = Section::with('schoolClass')->get();

        // initialize
        $students = collect();
        $existing = [];

        if ($request->filled('section_id')) {
            // determine date (fall back to today)
            $date = $request->input('date', today()->format('Y-m-d'));

            // 1) load all students in that section
            $students = Student::where('section_id', $request->section_id)
                            ->orderBy('name')
                            ->get();

            // 2) pull any attendance already recorded for that date
            $existing = Attendance::where('date', $date)
                                ->whereIn('student_id', $students->pluck('id'))
                                ->pluck('status', 'student_id')
                                ->toArray();
        }

        return view('pages.attendance.mark', compact('sections','students','existing'));
    }


    public function store(Request $request)
    {
        $this->authorize('manage-attendance');

        $request->validate([
            'date' => 'required|date:Y-m-d',
            'section_id' => 'required|exists:sections,id',
            'attendance' => 'required|array|min:1',
        ]);

        //validate date max date today
        if ($request->date > today()) {
            return back()->withErrors(['date' => 'Date cannot be in the future.']);
        }


        $errors=[];
        // check every student should present or absent can not be other status
        foreach ($request->attendance as $student_id => $status) {

            if (!$status || !in_array($status, ['Present', 'Absent'])) {
                $errors[] = "Invalid status for student $student_id.";
            }
        }

        if (count($errors) > 0) {
            return back()->withErrors($errors);
        }

        DB::beginTransaction();

        try {
            foreach ($request->attendance as $student_id => $status) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student_id,
                        'date' => $request->date,
                    ],
                    [
                        'status' => $status,
                    ]
                );
            }

            DB::commit();
            return redirect()->route('attendance.create', ['section_id' => $request->section_id])
                ->with('success', 'Attendance recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            //return back with error
            return back()->withErrors(['error' => $e->getMessage()]);
        }

    }


     /**
     * Display attendance report form and results.
     */
    public function report(Request $request)
    {
        $this->authorize('manage-attendance');

        // Fetch dropdown data
        $classes  = SchoolClass::with('sections')->get();
        $sections = Section::with('schoolClass')->get();

        // Default date range: current month
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        // default end date can not cross today
        $endDate   = $request->input('end_date',   today()->format('Y-m-d'));

        // Merge for validation
        $request->merge([
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        // Validate inputs
        $request->validate([
            'start_date' => 'required|date|before_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date|before_or_equal:today',
            'class_id'   => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        // Initialize as empty collections
        $students     = collect();
        $datesByMonth = collect();
        $records      = collect();

        if ($request->filled('class_id')) {
            try {
                // 1) Fetch students filtered by class/section
                $students = Student::when($request->filled('class_id'), function($q) use ($request) {
                                    $q->whereHas('section.schoolClass', fn($q2) =>
                                        $q2->where('id', $request->class_id)
                                    );
                                })
                                ->when($request->filled('section_id'), fn($q) =>
                                    $q->where('section_id', $request->section_id)
                                )
                                ->orderBy('name')
                                ->get();

                // 2) Get all attendance dates in range (and section)
                $dates = Attendance::whereBetween('date', [$startDate, $endDate])
                            ->when($request->filled('section_id'), fn($q) =>
                                $q->whereHas('student', fn($q2) =>
                                    $q2->where('section_id', $request->section_id)
                                )
                            )
                            ->distinct()
                            ->pluck('date')
                            ->sort()
                            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

                // Group dates by Month-Year label
                $datesByMonth = $dates->groupBy(fn($d) =>
                    Carbon::parse($d)->format('M-Y')
                );

                // 3) Load attendance into [student_id => [date => status]]
                $records = Attendance::whereBetween('date', [$startDate, $endDate])
                            ->whereIn('student_id', $students->pluck('id'))
                            ->get()
                            ->groupBy('student_id')
                            ->map(fn($group) =>
                                $group->pluck('status','date')->toArray()
                            );

                return view('pages.attendance.report', compact(
                    'classes','sections','students',
                    'startDate','endDate',
                    'datesByMonth','records'
                ));

            } catch (\Exception $e) {
                // log the error for debugging
                Log::error("Attendance report generation failed: {$e->getMessage()}", [
                    'stack' => $e->getTraceAsString()
                ]);

                // send a friendly message back
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Failed to generate attendance report. Please try again or contact support.');
            }

        }

         return view('pages.attendance.report', compact(
            'classes','sections','students',
            'startDate','endDate',
            'datesByMonth','records'
        ));

    }


     /**
     * Generate & download the attendance report as PDF.
     */
    public function reportPdf(Request $request)
    {
        $this->authorize('manage-attendance');

        // same defaults & validation as report()
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date',   Carbon::now()->endOfMonth()->format('Y-m-d'));
        $request->merge(compact('startDate','endDate'));

        $request->validate([
            'start_date' => 'required|date|before_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date|before_or_equal:today',
            'class_id'   => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

          // Initialize as empty collections
        $students     = collect();
        $datesByMonth = collect();
        $records      = collect();

        if ($request->filled('class_id')) {
            // fetch students
            $students = Student::when($request->filled('class_id'), fn($q) =>
                            $q->whereHas('section.schoolClass', fn($q2) =>
                                $q2->where('id', $request->class_id)
                            )
                        )
                        ->when($request->filled('section_id'), fn($q) =>
                            $q->where('section_id', $request->section_id)
                        )
                        ->orderBy('name')
                        ->get();

            // fetch all attendance dates in range
            $dates = Attendance::whereBetween('date', [$startDate, $endDate])
                        ->when($request->filled('section_id'), fn($q) =>
                            $q->whereHas('student', fn($q2) =>
                                $q2->where('section_id', $request->section_id)
                            )
                        )
                        ->distinct()
                        ->pluck('date')
                        ->sort()
                        ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

            // group by month
            $datesByMonth = $dates->groupBy(fn($d) =>
                Carbon::parse($d)->format('M-Y')
            );

            // load records [student_id => [date => status]]
            $records = Attendance::whereBetween('date', [$startDate, $endDate])
                        ->whereIn('student_id', $students->pluck('id'))
                        ->get()
                        ->groupBy('student_id')
                        ->map(fn($group) =>
                            $group->pluck('status','date')->toArray()
                        );

            // load dropdown data (if you want it in header)
            $classes  = SchoolClass::with('sections')->get();
            $sections = Section::with('schoolClass')->get();
        }



        // new — load a dedicated PDF template instead:
        $pdf = PDF::loadView('pages.attendance.report_pdf', [
            'classes'      => $classes,
            'sections'     => $sections,
            'students'     => $students,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'datesByMonth' => $datesByMonth,
            'records'      => $records,
        ])
        ->setPaper('a4', 'landscape');

        $filename = "attendance_report_{$startDate}_{$endDate}.pdf";

        return $pdf->download($filename);
    }


}
