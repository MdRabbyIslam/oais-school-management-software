<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $this->authorize('manage-academic-years');
        $years = AcademicYear::all();
        return view('pages.academic_years.index', compact('years'));
    }

    public function create()
    {
        $this->authorize('manage-academic-years');
        return view('pages.academic_years.create');
    }

    public function store(Request $request)
    {
        $this->authorize('manage-academic-years');

        // Convert the 'on' (or missing) into a real boolean
        $request->merge([
            'is_current' => $request->has('is_current'),
        ]);

        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:academic_years,name',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'is_current' => 'nullable|boolean',
        ]);

        $year = AcademicYear::create($request->only([
            'name',
            'start_date',
            'end_date'
        ]));

        if ($request->boolean('is_current')) {
            AcademicYear::setCurrent($year);
        }

        return redirect()->route('academic_years.index')
                         ->with('success', 'Academic Year created successfully.');
    }

    public function edit(AcademicYear $academicYear)
    {
        $this->authorize('manage-academic-years');
        return view('pages.academic_years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $this->authorize('manage-academic-years');

        // Convert the 'on' (or missing) into a real boolean
        $request->merge([
            'is_current' => $request->has('is_current'),
        ]);

        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'is_current' => 'nullable|boolean',
        ]);

        $academicYear->update($request->only([
            'name',
            'start_date',
            'end_date'
        ]));

        if ($request->boolean('is_current')) {
            AcademicYear::setCurrent($academicYear);
        }

        return redirect()->route('academic_years.index')
                         ->with('success', 'Academic Year updated successfully.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $this->authorize('manage-academic-years');
        $academicYear->delete();

        return redirect()->route('academic_years.index')
                         ->with('success', 'Academic Year deleted successfully.');
    }
}
