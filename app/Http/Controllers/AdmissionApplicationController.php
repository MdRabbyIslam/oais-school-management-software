<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\AdmissionDocument;
use App\Models\AdmissionApplicationStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdmissionApplicationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-students');

        $query = AdmissionApplication::with('academicYear')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate(50)->withQueryString();

        return view('pages.admissions.index', compact('applications'));
    }

    public function create()
    {
        $this->authorize('manage-students');

        $academicYears = \App\Models\AcademicYear::all();
        $classes = \App\Models\SchoolClass::with('sections')->get();

        return view('pages.admissions.create', compact('academicYears','classes'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage-students');

        // Build base validation rules
        $rules = [
            'academic_year_id' => 'required|exists:academic_years,id',
            'preferred_class_id' => 'nullable|exists:classes,id',
            'preferred_section_id' => 'nullable|exists:sections,id',
            'name' => 'required|string',
            'dob' => 'nullable|date',
            'primary_guardian_name' => 'nullable|string',
            'primary_guardian_contact' => 'nullable|string',
            'primary_guardian_relation' => 'nullable|string',
            'secondary_guardian_name' => 'nullable|string',
            'secondary_guardian_contact' => 'nullable|string',
            'secondary_guardian_relation' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'address' => 'nullable|string',
        ];

        // Add document rules dynamically from AdmissionDocument::TYPES
        foreach (\App\Models\AdmissionDocument::TYPES as $type => $label) {
            $rules["documents.{$type}"] = 'nullable|file|mimes:pdf,jpg,jpeg,png';
        }

        $data = $request->validate($rules);

        $app = AdmissionApplication::create(array_merge($data, [
            'application_no' => 'ADM-'.date('Y').'-'.Str::random(6),
            'source' => 'internal',
            'submitted_by_user_id' => auth()->id(),
            'submitted_at' => now(),
        ]));

        // handle typed file uploads (birth_certificate, marksheet)
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $type => $file) {
                if (! $file) continue;
                // Only allow known types
                if (! array_key_exists($type, \App\Models\AdmissionDocument::TYPES)) continue;
                $path = $file->store('documents/admissions', 'public_upload');
                AdmissionDocument::create([
                    'admission_application_id' => $app->id,
                    'type' => $type,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return redirect()->route('admissions.index')->with('success', 'Application created.');
    }

    public function show(AdmissionApplication $admission)
    {
        $this->authorize('manage-students');

        $admission->load(['documents','logs','academicYear']);

        $sections = \App\Models\Section::with('schoolClass')
            ->when($admission->preferred_class_id, function ($query) use ($admission) {
                $query->where('class_id', $admission->preferred_class_id);
            })
            ->get();

        return view('pages.admissions.show', [
            'application' => $admission,
            'sections' => $sections,
        ]);
    }

    public function edit(AdmissionApplication $admission)
    {
        $this->authorize('manage-students');

        // Prevent editing if already approved or rejected (optional logic)
        if ($admission->status !== 'pending') {
            return redirect()->route('admissions.show', $admission->id)
                ->with('error', 'Only pending applications can be edited.');
        }

        $academicYears = \App\Models\AcademicYear::all();
        $classes = \App\Models\SchoolClass::with('sections')->get();

        // Load existing documents to show them in the view
        $admission->load('documents');

        return view('pages.admissions.edit', compact('admission', 'academicYears', 'classes'));
    }

    public function update(Request $request, AdmissionApplication $admission)
    {
        $this->authorize('manage-students');

        $rules = [
            'academic_year_id' => 'required|exists:academic_years,id',
            'preferred_class_id' => 'nullable|exists:classes,id',
            'preferred_section_id' => 'nullable|exists:sections,id',
            'name' => 'required|string',
            'dob' => 'nullable|date',
            'primary_guardian_name' => 'nullable|string',
            'primary_guardian_contact' => 'nullable|string',
            'primary_guardian_relation' => 'nullable|string',
            'secondary_guardian_name' => 'nullable|string',
            'secondary_guardian_contact' => 'nullable|string',
            'secondary_guardian_relation' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'address' => 'nullable|string',
        ];

        foreach (\App\Models\AdmissionDocument::TYPES as $type => $label) {
            $rules["documents.{$type}"] = 'nullable|file|mimes:pdf,jpg,jpeg,png';
        }

        $data = $request->validate($rules);

        // Update the main record
        $admission->update($data);

        // Handle File Updates
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $type => $file) {
                if (!$file || !array_key_exists($type, \App\Models\AdmissionDocument::TYPES)) continue;

                // Optional: Delete the old document record and file of the same type
                $oldDoc = $admission->documents()->where('type', $type)->first();
                if ($oldDoc) {
                    \Storage::disk('public_upload')->delete($oldDoc->path);
                    $oldDoc->delete();
                }

                $path = $file->store('documents/admissions', 'public_upload');
                AdmissionDocument::create([
                    'admission_application_id' => $admission->id,
                    'type' => $type,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return redirect()->route('admissions.show', $admission->id)->with('success', 'Application updated successfully.');
    }

    public function approve(Request $request, AdmissionApplication $admission)
    {
        $this->authorize('manage-students');

        // inside transaction: create Student and StudentEnrollment as in plan
        \DB::transaction(function () use ($request, $admission) {
            $data = $request->validate([
                'section_id' => [
                    'required',
                    Rule::exists('sections', 'id')->where(function ($query) use ($admission) {
                        if ($admission->preferred_class_id) {
                            $query->where('class_id', $admission->preferred_class_id);
                        }
                    }),
                ],
                'admission_date' => 'required|date',
                'roll_number' => 'nullable|integer',
            ]);

            $studentData = [
                'name' => $admission->name,
                'dob' => $admission->dob,
                'blood_group' => $admission->blood_group,
                'primary_guardian_name' => $admission->primary_guardian_name,
                'primary_guardian_contact' => $admission->primary_guardian_contact,
                'primary_guardian_relation' => $admission->primary_guardian_relation,
                'section_id' => $data['section_id'],
                'admission_date' => $data['admission_date'],
                'roll_number' => $data['roll_number'] ?? null,
            ];

            $student = \App\Models\Student::create($studentData);

            \App\Models\StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $admission->academic_year_id,
                'class_id' => \App\Models\Section::find($data['section_id'])->class_id,
                'section_id' => $data['section_id'],
                'status' => 'active',
                'enrollment_date' => $data['admission_date'],
            ]);

            // update application
            $admission->status = 'approved';
            $admission->approved_student_id = $student->id;
            $admission->reviewed_by_user_id = auth()->id();
            $admission->reviewed_at = now();
            $admission->save();

            AdmissionApplicationStatusLog::create([
                'admission_application_id' => $admission->id,
                'from_status' => 'pending',
                'to_status' => 'approved',
                'changed_by_user_id' => auth()->id(),
                'notes' => $request->input('review_notes') ?? null,
            ]);
        });

        return redirect()->route('admissions.show', $admission->id)->with('success', 'Application approved and student created.');
    }

    public function reject(Request $request, AdmissionApplication $admission)
    {
        $this->authorize('manage-students');

        $data = $request->validate(['reason' => 'nullable|string']);

        $admission->status = 'rejected';
        $admission->reviewed_by_user_id = auth()->id();
        $admission->reviewed_at = now();
        $admission->review_notes = $data['reason'] ?? null;
        $admission->save();

        AdmissionApplicationStatusLog::create([
            'admission_application_id' => $admission->id,
            'from_status' => 'pending',
            'to_status' => 'rejected',
            'changed_by_user_id' => auth()->id(),
            'notes' => $data['reason'] ?? null,
        ]);

        return redirect()->route('admissions.show', $admission->id)->with('success', 'Application rejected.');
    }
}
