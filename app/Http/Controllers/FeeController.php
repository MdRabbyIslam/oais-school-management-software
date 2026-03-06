<?php

namespace App\Http\Controllers;

use App\Models\ClassFeeAmount;
use App\Models\Fee;
use App\Models\FeeGroup;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $fees = Fee::with('feeGroup')
            ->when($search, function($query) use ($search) {
                return $query->where('fee_name', 'like', "%$search%")
                           ->orWhereHas('feeGroup', function($q) use ($search) {
                               $q->where('name', 'like', "%$search%");
                           });
            })
            // ->orderBy('fee_name')
            ->orderBy('sl_no')
            ->orderBy('id') // tie-breaker
            ->paginate(20);


        return view('pages.fees.index', compact('fees', 'search'));
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer', // each is a fee id
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->order as $index => $feeId) {
                Fee::whereKey($feeId)->update(['sl_no' => $index + 1]);
            }
        });

        return response()->json(['status' => 'ok']);
    }


    public function create()
    {

        return view('pages.fees.create', [

            'groups' => FeeGroup::all(),
            'classes' => SchoolClass::all(),
            'classAmounts' => []
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fee_group_id' => 'required|exists:fee_groups,id',
            'fee_name' => 'required|string|max:255',
            'billing_type' => 'required|in:recurring,one-time,term-based',
            'frequency' => 'nullable|in:monthly,quarterly,termly,annual',
            'is_mandatory' => 'boolean',
            'class_amounts' => 'required|array',
            'class_amounts.*' => 'numeric|min:0'
        ]);

        $fee = Fee::create($request->only([
            'fee_group_id', 'fee_name', 'billing_type',
            'frequency', 'is_mandatory'
        ]));

        // Save class amounts
        foreach ($request->class_amounts as $classId => $amount) {
            if (!is_null($amount)) {
                ClassFeeAmount::updateOrCreate(
                    ['fee_id' => $fee->id, 'class_id' => $classId],
                    ['amount' => $amount]
                );
            }
        }

        return redirect()->route('fees.index')
            ->with('success', 'Fee created successfully.');
    }

    public function edit($id)
    {
        $fee = Fee::with('classFeeAmounts')->findOrFail($id);

        // Check if the fee has any class amounts
        if ($fee->classFeeAmounts->isEmpty()) {
            return redirect()->route('fees.index')
                ->with('error', 'No class amounts found for this fee.');
        }


        return view('pages.fees.edit', [
            'fee' => $fee,
            'groups' => FeeGroup::all(),
            'classes' => SchoolClass::all(),
            'classAmounts' => $fee->classFeeAmounts->pluck('amount', 'class_id')->toArray()
        ]);
    }

    public function update(Request $request, Fee $fee)
    {
        $validated = $request->validate([
            'fee_group_id'    => 'required|exists:fee_groups,id',
            'fee_name'        => 'required|string|max:255|unique:fees,fee_name,'.$fee->id,
            'billing_type'    => 'required|in:recurring,one-time,term-based',
            'frequency'       => 'nullable|in:monthly,quarterly,termly,annual',
            'is_mandatory'    => 'boolean',
            'class_amounts'   => 'required|array',                 // ← validate
            'class_amounts.*' => 'nullable|numeric|min:0',        // ← per-class
        ]);

        // Update the fee itself
        $fee->update($request->only([
            'fee_group_id', 'fee_name', 'billing_type',
            'frequency', 'is_mandatory'
        ]));

        // **New**: update class-specific amounts
        foreach ($request->input('class_amounts', []) as $classId => $amount) {
            // if blank, you can choose to delete instead:
            if ($amount === null || $amount === '') {
                ClassFeeAmount::where([
                    'fee_id'   => $fee->id,
                    'class_id' => $classId,
                ])->delete();
            } else {
                ClassFeeAmount::updateOrCreate(
                    ['fee_id' => $fee->id, 'class_id' => $classId],
                    ['amount' => $amount]
                );
            }
        }

        return redirect()->route('fees.index')
                        ->with('success', 'Fee Updated successfully.');
    }


    public function destroy(Fee $fee)
    {
        if ($fee->feeAssignments()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete fee with existing assignments.');
        }

        $fee->delete();
        return redirect()->route('fees.index')
            ->with('success', 'Fee deleted successfully.');
    }
}
