<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPromotion;
use App\Services\PromotionService;
use Illuminate\Http\Request;

class StudentPromotionController extends Controller
{
    protected $service;

    public function __construct(PromotionService $service)
    {
        $this->service = $service;
    }

    // Show form to request promotion for single student
    public function create(Student $student)
    {
        $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
        $academicYears = \App\Models\AcademicYear::all();
        $classes = \App\Models\SchoolClass::all();
        $sections = \App\Models\Section::all();

        return view('pages.students.promote', compact('student', 'currentEnrollment', 'academicYears', 'classes', 'sections'));
    }

    // Store promotion request
    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'from_enrollment_id' => 'required|integer|exists:student_enrollments,id',
            'target_academic_year_id' => 'required|integer|exists:academic_years,id',
            'target_class_id' => 'required|integer|exists:classes,id',
            'target_section_id' => 'required|integer|exists:sections,id',
            'reason' => 'nullable|string',
        ]);

        $payload = [
            'student_id' => $student->id,
            'from_enrollment_id' => $data['from_enrollment_id'],
            'target_academic_year_id' => $data['target_academic_year_id'],
            'target_class_id' => $data['target_class_id'],
            'target_section_id' => $data['target_section_id'],
            'reason' => $data['reason'] ?? null,
            'requested_by_user_id' => auth()->id(),
            'requested_at' => now(),
            'auto_promotion' => false,
        ];

        $promotion = $this->service->request($payload);

        return redirect()->route('promotions.show', $promotion->id)->with('success', 'Promotion request created');
    }

    public function show(StudentPromotion $promotion)
    {
        $promotion->load(['student', 'fromEnrollment', 'toEnrollment', 'targetAcademicYear', 'targetClass', 'targetSection', 'requestedBy', 'reviewedBy']);
        return view('pages.students.promotion_show', compact('promotion'));
    }

    // List promotions (pending approvals)
    public function index(Request $request)
    {
        $query = StudentPromotion::with([
            'student',
            'fromEnrollment.schoolClass',
            'fromEnrollment.section',
            'targetAcademicYear',
            'targetClass',
            'targetSection',
            'requestedBy'
        ])->where('status', 'pending')
          ->orderByDesc('created_at');

        // Filters: class, section, search
        if ($request->filled('class_id')) {
            $classId = $request->input('class_id');
            $query->whereHas('fromEnrollment.schoolClass', function ($q) use ($classId) {
                $q->where('id', $classId);
            });
        }

        if ($request->filled('section_id')) {
            $query->whereHas('fromEnrollment', function ($q) use ($request) {
                $q->where('section_id', $request->input('section_id'));
            });
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->whereHas('student', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('student_id', 'like', "%{$term}%");
            });
        }

        $promotions = $query->paginate(50)->withQueryString();

        // classes and sections for filters
        $classes = \App\Models\SchoolClass::with('sections')->get();
        $sections = \App\Models\Section::select('id','class_id','section_name')->get();

        return view('pages.promotions.index', compact('promotions', 'classes', 'sections'));
    }

    // Bulk promotion form + listing matching students
    public function bulkForm(Request $request)
    {
        $this->authorize('manage-promotions');

        $classes = \App\Models\SchoolClass::with('sections')->get();
        $sections = \App\Models\Section::select('id','class_id','section_name')->get();
        $academicYears = \App\Models\AcademicYear::all();

        $enrollments = null;
        if ($request->filled('source_academic_year_id') && $request->filled('source_class_id')) {
            $q = \App\Models\StudentEnrollment::with(['student','section','schoolClass'])
                ->where('academic_year_id', $request->input('source_academic_year_id'))
                ->where('class_id', $request->input('source_class_id'))
                ->where('status', 'active');

            if ($request->filled('source_section_id')) {
                $q->where('section_id', $request->input('source_section_id'));
            }

            $enrollments = $q->get();
        }

        return view('pages.promotions.bulk', compact('classes','sections','academicYears','enrollments'));
    }

    // Store bulk promotion requests
    public function bulkStore(Request $request)
    {
        $this->authorize('manage-promotions');

        $data = $request->validate([
            'selected_enrollments' => 'required|array|min:1',
            'selected_enrollments.*' => 'integer|exists:student_enrollments,id',
            'target_academic_year_id' => 'required|integer|exists:academic_years,id',
            'target_class_id' => 'required|integer|exists:classes,id',
            'target_section_id' => 'required|integer|exists:sections,id',
        ]);

        $rows = [];
        $enrollments = \App\Models\StudentEnrollment::whereIn('id', $data['selected_enrollments'])->get();
        foreach ($enrollments as $e) {
            $rows[] = [
                'student_id' => $e->student_id,
                'from_enrollment_id' => $e->id,
                'target_academic_year_id' => $data['target_academic_year_id'],
                'target_class_id' => $data['target_class_id'],
                'target_section_id' => $data['target_section_id'],
                'reason' => null,
                'auto_promotion' => false,
            ];
        }

        $created = $this->service->bulkRequest($rows, auth()->id());

        return redirect()->route('promotions.index')->with('success', 'Created '.count($created).' promotion request(s).');
    }

    public function approve(Request $request, StudentPromotion $promotion)
    {
        $this->authorize('manage-promotions');
        $data = $request->validate([
            'roll_number' => 'nullable|integer',
            'enrollment_date' => 'nullable|date',
        ]);

        $approverId = auth()->id();
        $this->service->approve($promotion, $approverId, $data);

        return redirect()->back()->with('success', 'Promotion approved');
    }

    public function reject(Request $request, StudentPromotion $promotion)
    {
        $this->authorize('manage-promotions');

        $data = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $this->service->reject($promotion, auth()->id(), $data['reason'] ?? null);

        return redirect()->back()->with('success', 'Promotion rejected');
    }

    // Bulk approve selected promotions
    public function bulkApprove(Request $request)
    {
        $this->authorize('manage-promotions');

        $data = $request->validate([
            'selected_promotions' => 'required|array|min:1',
            'selected_promotions.*' => 'integer|exists:student_promotions,id',
        ]);

        $approved = $this->service->bulkApprove($data['selected_promotions'], auth()->id());

        return redirect()->back()->with('success', 'Approved '.count($approved).' promotion(s).');
    }

    public function bulkReject(Request $request)
    {
        $this->authorize('manage-promotions');

        $data = $request->validate([
            'selected_promotions' => 'required|array|min:1',
            'selected_promotions.*' => 'integer|exists:student_promotions,id',
            'reason' => 'nullable|string',
        ]);

        $rejected = $this->service->bulkReject(
            $data['selected_promotions'],
            auth()->id(),
            $data['reason'] ?? null
        );

        return redirect()->back()->with('success', 'Rejected '.count($rejected).' promotion(s).');
    }
}
