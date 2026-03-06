<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_subject_id',
        'student_enrollment_id',
        'marks_obtained',
        'is_absent',
        'remarks',
        'entered_by',
        'verified_by',
        'entered_at',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'is_absent' => 'boolean',
        'entered_at' => 'datetime',
    ];

    public function assessmentSubject()
    {
        return $this->belongsTo(ExamAssessmentSubject::class, 'assessment_subject_id');
    }

    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function components()
    {
        return $this->hasMany(ExamMarkComponent::class);
    }
}

