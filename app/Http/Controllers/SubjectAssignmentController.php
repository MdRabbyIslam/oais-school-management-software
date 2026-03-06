<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\SubjectAssignment;
use Illuminate\Http\Request;

class SubjectAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-subjects');

        $sections = Section::with('schoolClass')->get();
        $teachers = Teacher::all();
        $subjects = [];
        $existing = [];

        if ($request->filled('section_id')) {
            $sectionId = $request->section_id;
            $subjects = Subject::whereHas('classes', function ($q) use ($sectionId) {
                $q->where('classes.id', Section::find($sectionId)->class_id);
            })->get();

            

            $existingAssignments = SubjectAssignment::where('section_id', $sectionId)->get();
            $existing = $existingAssignments->pluck('teacher_id', 'subject_id')->toArray();
        }

        return view('pages.subject-assignments.index', compact('sections', 'teachers', 'subjects', 'existing'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage-subjects');

        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'assignments' => 'required|array',
        ]);

        $sectionId = $request->section_id;
        $assignments = $request->assignments;

        foreach ($assignments as $subjectId => $teacherId) {
            if ($teacherId) {
                SubjectAssignment::updateOrCreate(
                    [
                        'subject_id' => $subjectId,
                        'section_id' => $sectionId,
                    ],
                    [
                        'teacher_id' => $teacherId,
                    ]
                );
            }
        }

        return redirect()->route('subject-assignments.index', ['section_id' => $sectionId])
            ->with('success', 'Subject assignments saved successfully.');
    }
}
