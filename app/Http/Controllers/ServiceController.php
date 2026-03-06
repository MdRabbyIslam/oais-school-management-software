<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Show the form for updating a service (fee) for a student.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\View\View
     */
    public function showServiceForm(Request $request, Student $student)
    {
        // Get the list of services (fees) assigned to the student
        $services = $student->fees;

        // Pass the student and services to the view
        return view('pages.services.edit', compact('student', 'services'));
    }

    public function updateService(Request $request, Student $student)
    {
        // Find the service (fee) based on the service ID
        $service = Fee::find($request->service_id);

        // Pause the service
        if ($request->pause_start_date) {
            $student->feeAssignments()->updateExistingPivot($service->id, [
                'pause_start_date' => $request->pause_start_date,
                'pause_end_date' => $request->pause_end_date,
                'is_paused' => true,
            ]);
        }

        // Resume the service
        if ($request->pause_end_date) {
            $student->feeAssignments()->updateExistingPivot($service->id, [
                'pause_end_date' => null,
                'is_paused' => false,
            ]);
        }

        return redirect()->route('students.show', $student->id)->with('success', 'Service status updated successfully!');
    }

}
