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
        $subjects = Subject::all();
        return view('pages.subjects.index', compact('subjects'));
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
            $subject->classes()->sync($request->classes ?? []);
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
            $subject->classes()->sync($request->classes ?? []);
        });

        return redirect()->route('subjects.index')->with('success', 'Subject updated with class assignments.');
    }

    public function destroy(Subject $subject)
    {
        $this->authorize('manage-subjects');
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
