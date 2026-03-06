<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPromotion extends Model
{
    protected $table = 'student_promotions';

    protected $fillable = [
        'student_id',
        'from_enrollment_id',
        'to_enrollment_id',
        'target_academic_year_id',
        'target_class_id',
        'target_section_id',
        'status',
        'auto_promotion',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'requested_at',
        'reviewed_at',
        'reason',
        'meta'
    ];

    protected $casts = [
        'auto_promotion' => 'boolean',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fromEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'from_enrollment_id');
    }

    public function toEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'to_enrollment_id');
    }

    public function targetAcademicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'target_academic_year_id');
    }

    public function targetClass()
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }

    public function targetSection()
    {
        return $this->belongsTo(Section::class, 'target_section_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
