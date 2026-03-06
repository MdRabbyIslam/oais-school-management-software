<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index()
    {
        $this->authorize('manage-terms');
        $terms = Term::with('academicYear')->orderBy('order')->get();
        return view('pages.terms.index', compact('terms'));
    }

    public function create()
    {
        $this->authorize('manage-terms');
        $years = AcademicYear::orderBy('start_date', 'desc')->get();
        return view('pages.terms.create', compact('years'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage-terms');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name'             => 'required|string|max:30',
            'order'            => 'required|integer|min:1',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'fee_due_date'     => 'nullable|date|after_or_equal:start_date|before_or_equal:end_date',
        ]);

        Term::create($validated);

        return redirect()->route('terms.index')
                         ->with('success', 'Term created successfully.');
    }

    public function edit(Term $term)
    {
        $this->authorize('manage-terms');
        $years = AcademicYear::orderBy('start_date', 'desc')->get();
        return view('pages.terms.edit', compact('term','years'));
    }

    public function update(Request $request, Term $term)
    {
        $this->authorize('manage-terms');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name'             => 'required|string|max:30',
            'order'            => 'required|integer|min:1',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'fee_due_date'     => 'nullable|date|after_or_equal:start_date|before_or_equal:end_date',
        ]);

        $term->update($validated);

        return redirect()->route('terms.index')
                         ->with('success', 'Term updated successfully.');
    }

    public function destroy(Term $term)
    {
        $this->authorize('manage-terms');
        $term->delete();

        return redirect()->route('terms.index')
                         ->with('success', 'Term deleted successfully.');
    }
}
