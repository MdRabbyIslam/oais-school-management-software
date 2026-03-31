<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Display a listing of the classes.
     */
    public function index()
    {
        $this->authorize('manage-classes');
        $classes = SchoolClass::all();

        return view('pages.classes.index', compact('classes'));
    }

    /**
     * Show the form for creating a new class.
     */
    public function create()
    {
        $this->authorize('manage-classes');
        return view('pages.classes.create');
    }

    /**
     * Store a newly created class in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('manage-classes');

        $request->validate([
            'name' => 'required|string|max:255',
            'class_level' => 'required|integer|min:-1',
        ]);

        SchoolClass::create($request->only('name', 'class_level'));

        return redirect()->route('classes.index')->with('success', 'Class created successfully.');
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(SchoolClass $class)
    {
        $this->authorize('manage-classes');
        return view('pages.classes.edit', compact('class'));
    }

    /**
     * Update the specified class in storage.
     */
    public function update(Request $request, SchoolClass $class)
    {
        $this->authorize('manage-classes');

        $request->validate([
            'name' => 'required|string|max:255',
            'class_level' => 'required|integer|min:-1',
        ]);

        $class->update($request->only('name', 'class_level'));

        return redirect()->route('classes.index')->with('success', 'Class updated successfully.');
    }

    /**
     * Remove the specified class from storage.
     */
    public function destroy(SchoolClass $class)
    {
        $this->authorize('manage-classes');
        $class->delete();
        return redirect()->route('classes.index')->with('success', 'Class deleted.');
    }
}
