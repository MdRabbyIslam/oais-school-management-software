<?php

namespace App\Http\Controllers;

use App\Models\FeeGroup;
use Illuminate\Http\Request;

class FeeGroupController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $groups = FeeGroup::when($search, function($query) use ($search) {
                    return $query->where('name', 'like', "%$search%")
                                 ->orWhere('type', 'like', "%$search%");
                })
                ->orderBy('name')
                ->paginate(20);

        return view('pages.fee_groups.index', compact('groups', 'search'));
    }

    public function create()
    {
        return view('pages.fee_groups.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:fee_groups',
            'type' => 'required|in:core,service,penalty'
        ]);

        FeeGroup::create($request->only(['name', 'type']));

        return redirect()->route('fee-groups.index')
            ->with('success', 'Fee group created successfully.');
    }

    public function edit(FeeGroup $feeGroup)
    {
        return view('pages.fee_groups.form', compact('feeGroup'));
    }

    public function update(Request $request, FeeGroup $feeGroup)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:fee_groups,name,'.$feeGroup->id,
            'type' => 'required|in:core,service,penalty'
        ]);

        $feeGroup->update($request->only(['name', 'type']));

        return redirect()->route('fee-groups.index')
            ->with('success', 'Fee group updated successfully.');
    }

    public function destroy(FeeGroup $feeGroup)
    {
        if ($feeGroup->fees()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete group with associated fees.');
        }

        $feeGroup->delete();
        return redirect()->route('fee-groups.index')
            ->with('success', 'Fee group deleted successfully.');
    }
}
