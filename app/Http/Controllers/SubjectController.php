<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function index()
    {
        $this->authorize('manage-subjects');
        $classes = SchoolClass::orderBy('class_level')->orderBy('name')->get();
        $selectedClassId = request('class_id');
        $selectedClass = null;

        if ($selectedClassId) {
            $selectedClass = SchoolClass::findOrFail($selectedClassId);
            $subjects = $selectedClass->subjects()
                ->orderBy('class_subject.sort_order')
                ->orderBy('subjects.name')
                ->get();
        } else {
            $subjects = Subject::orderBy('name')->get();
        }

        return view('pages.subjects.index', compact('subjects', 'classes', 'selectedClass', 'selectedClassId'));
    }

    public function create()
    {
        $this->authorize('manage-subjects');

        $classes = SchoolClass::all();
        return view('pages.subjects.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage-subjects');

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'description' => 'nullable|string',
            'classes' => 'nullable|array',
            'classes.*' => 'exists:classes,id',
        ]);

        DB::transaction(function () use ($request) {
            $subject = Subject::create($request->only('name', 'code', 'description'));
            $subject->classes()->sync($this->buildClassSyncPayload($request->input('classes', [])));
        });

        return redirect()->route('subjects.index')->with('success', 'Subject created with class assignments.');
    }

    public function edit(Subject $subject)
    {
        $this->authorize('manage-subjects');

        $classes = SchoolClass::all();
        return view('pages.subjects.edit', compact('subject', 'classes'));
    }

    public function update(Request $request, Subject $subject)
    {
        $this->authorize('manage-subjects');

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'description' => 'nullable|string',
            'classes' => 'nullable|array',
            'classes.*' => 'exists:classes,id',
        ]);

        DB::transaction(function () use ($request, $subject) {
            $subject->update($request->only('name', 'code', 'description'));
            $subject->classes()->sync($this->buildClassSyncPayload($request->input('classes', []), $subject));
        });

        return redirect()->route('subjects.index')->with('success', 'Subject updated with class assignments.');
    }

    public function destroy(Subject $subject)
    {
        $this->authorize('manage-subjects');
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
    }

    public function reorder(Request $request, SchoolClass $class)
    {
        $this->authorize('manage-subjects');

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        $assignedSubjectIds = $class->subjects()->pluck('subjects.id')->all();

        foreach ($validated['order'] as $subjectId) {
            abort_unless(in_array($subjectId, $assignedSubjectIds), 422, 'Invalid subject order payload.');
        }

        DB::transaction(function () use ($validated, $class) {
            foreach ($validated['order'] as $index => $subjectId) {
                DB::table('class_subject')
                    ->where('class_id', $class->id)
                    ->where('subject_id', $subjectId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    private function buildClassSyncPayload(array $classIds, ?Subject $subject = null): array
    {
        $payload = [];
        $existingSortOrders = $subject
            ? $subject->classes->mapWithKeys(fn ($class) => [$class->id => (int) ($class->pivot->sort_order ?? 0)])->all()
            : [];

        foreach ($classIds as $classId) {
            $payload[$classId] = [
                'sort_order' => $existingSortOrders[$classId] ?? $this->nextSortOrderForClass((int) $classId),
            ];
        }

        return $payload;
    }

    private function nextSortOrderForClass(int $classId): int
    {
        return ((int) DB::table('class_subject')->where('class_id', $classId)->max('sort_order')) + 1;
    }
}
