<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradingPolicyRequest;
use App\Http\Requests\UpdateGradingPolicyRequest;
use App\Models\GradeScheme;
use App\Models\GradingPolicy;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradingPolicyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-exams');

        $query = GradingPolicy::with(['schoolClass', 'subject', 'gradeScheme', 'components']);

        if ($request->filled('class_id')) {
            $query->where('class_id', (int) $request->input('class_id'));
        }

        if ($request->filled('grade_scheme_id')) {
            $query->where('grade_scheme_id', (int) $request->input('grade_scheme_id'));
        }

        $policies = $query
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        $classes = SchoolClass::orderBy('class_level')->get();
        $schemes = GradeScheme::orderBy('name')->get();

        return view('pages.grading_policies_index', compact('policies', 'classes', 'schemes'));
    }

    public function create(\Illuminate\Http\Request $request)
    {
        $this->authorize('manage-exams');

        $classes = SchoolClass::orderBy('class_level')->get();
        $subjects = Subject::with(['classes:id'])
            ->orderBy('name')
            ->get();
        $schemes = GradeScheme::where('is_active', true)->orderBy('name')->get();
        $prefillClassId = $request->query('class_id');
        $prefillSubjectId = $request->query('subject_id');

        return view('pages.grading_policies_create', compact('classes', 'subjects', 'schemes', 'prefillClassId', 'prefillSubjectId'));
    }

    public function store(StoreGradingPolicyRequest $request)
    {
        $this->authorize('manage-exams');

        DB::transaction(function () use ($request) {
            $policy = GradingPolicy::create([
                'class_id' => (int) $request->input('class_id'),
                'subject_id' => (int) $request->input('subject_id'),
                'total_marks' => $request->input('total_marks'),
                'pass_marks' => $request->input('pass_marks'),
                'grade_scheme_id' => (int) $request->input('grade_scheme_id'),
                'is_optional' => (bool) $request->boolean('is_optional', false),
                'weight' => $request->input('weight', 1.00),
                'is_active' => (bool) $request->boolean('is_active', true),
            ]);

            $this->syncPolicyComponents($policy, $request->input('components', []));
        });

        return redirect()->route('grading-policies.index')->with('success', 'Grading policy created successfully.');
    }

    public function edit(GradingPolicy $gradingPolicy)
    {
        $this->authorize('manage-exams');

        $classes = SchoolClass::orderBy('class_level')->get();
        $subjects = Subject::orderBy('name')->get();
        $schemes = GradeScheme::orderBy('name')->get();

        $gradingPolicy->load('components');

        return view('pages.grading_policies_edit', compact('gradingPolicy', 'classes', 'subjects', 'schemes'));
    }

    public function update(UpdateGradingPolicyRequest $request, GradingPolicy $gradingPolicy)
    {
        $this->authorize('manage-exams');

        DB::transaction(function () use ($request, $gradingPolicy) {
            $gradingPolicy->update([
                'class_id' => (int) $request->input('class_id'),
                'subject_id' => (int) $request->input('subject_id'),
                'total_marks' => $request->input('total_marks'),
                'pass_marks' => $request->input('pass_marks'),
                'grade_scheme_id' => (int) $request->input('grade_scheme_id'),
                'is_optional' => (bool) $request->boolean('is_optional', false),
                'weight' => $request->input('weight', 1.00),
                'is_active' => (bool) $request->boolean('is_active', true),
            ]);

            $this->syncPolicyComponents($gradingPolicy, $request->input('components', []));
        });

        return redirect()->route('grading-policies.index')->with('success', 'Grading policy updated successfully.');
    }

    public function destroy(GradingPolicy $gradingPolicy)
    {
        $this->authorize('manage-exams');

        $inUse = $gradingPolicy->examAssessmentSubjects()->exists();
        if ($inUse) {
            return back()->with('error', 'Cannot delete grading policy because it is already used in exam setup.');
        }

        $gradingPolicy->delete();

        return redirect()->route('grading-policies.index')->with('success', 'Grading policy deleted successfully.');
    }

    private function syncPolicyComponents(GradingPolicy $gradingPolicy, array $components): void
    {
        $rows = collect($components)
            ->filter(fn ($component) => is_array($component) && !empty($component['component_name']) && !empty($component['component_code']))
            ->values();

        $gradingPolicy->components()->delete();
        foreach ($rows as $index => $component) {
            $gradingPolicy->components()->create([
                'component_name' => $component['component_name'],
                'component_code' => $component['component_code'],
                'total_marks' => $component['total_marks'],
                'pass_marks' => $component['pass_marks'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
