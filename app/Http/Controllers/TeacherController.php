<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function index()
    {
        $this->authorize('manage-teachers');
        $teachers = Teacher::all();
        return view('pages.teachers.index', compact('teachers'));
    }

    public function create()
    {
        $this->authorize('manage-teachers');
        return view('pages.teachers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('manage-teachers');

        $request->validate([
            'name' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'experience' => 'nullable|string',
            'contact_info' => 'required|string|max:255',
            'base_salary' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive,Resigned,Retired',
        ]);

        $data = $request->all();
        $data['teacher_id'] = Str::uuid();

        Teacher::create($data);

        return redirect()->route('teachers.index')->with('success', 'Teacher created successfully.');
    }

    public function edit(Teacher $teacher)
    {
        $this->authorize('manage-teachers');
        return view('pages.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $this->authorize('manage-teachers');

        $request->validate([
            'name' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'experience' => 'nullable|string',
            'contact_info' => 'required|string|max:255',
            'base_salary' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive,Resigned,Retired',
        ]);

        $teacher->update($request->all());

        return redirect()->route('teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $this->authorize('manage-teachers');
        $teacher->delete();
        return redirect()->route('teachers.index')->with('success', 'Teacher deleted.');
    }
}
