<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_no', 'academic_year_id', 'preferred_class_id', 'preferred_section_id',
        'name','dob','primary_guardian_name','primary_guardian_contact','primary_guardian_relation',
        'secondary_guardian_name','secondary_guardian_contact','secondary_guardian_relation','address','blood_group',
        'status','source','submitted_by_user_id','submitted_at','reviewed_by_user_id','reviewed_at',
        'approved_student_id','review_notes','meta'
    ];

    protected $casts = [
        'dob' => 'date', // Add this line
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function documents()
    {
        return $this->hasMany(AdmissionDocument::class);
    }

    public function logs()
    {
        return $this->hasMany(AdmissionApplicationStatusLog::class);
    }
}
