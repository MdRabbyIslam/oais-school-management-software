<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $this->authorize('manage-sections');
        $sections = Section::with('schoolClass')->get();
        return view('pages.sections.index', compact('sections'));
    }

    public function create()
    {
        $this->authorize('manage-sections');
        $classes = SchoolClass::all();
        return view('pages.sections.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage-sections');

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_name' => 'required|string|max:255',
        ]);

        Section::create($request->only('class_id', 'section_name'));

        return redirect()->route('sections.index')->with('success', 'Section created successfully.');
    }

    public function edit(Section $section)
    {
        $this->authorize('manage-sections');
        $classes = SchoolClass::all();
        return view('pages.sections.edit', compact('section', 'classes'));
    }

    public function update(Request $request, Section $section)
    {
        $this->authorize('manage-sections');

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_name' => 'required|string|max:255',
        ]);

        $section->update($request->only('class_id', 'section_name'));

        return redirect()->route('sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        $this->authorize('manage-sections');
        $section->delete();
        return redirect()->route('sections.index')->with('success', 'Section deleted.');
    }
}
