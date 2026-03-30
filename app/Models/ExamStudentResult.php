<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamStudentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_class_id',
        'student_enrollment_id',
        'total_obtained',
        'total_marks',
        'percentage',
        'gpa',
        'final_grade',
        'failed_subject_count',
        'is_pass',
        'position',
        'manual_position',
        'calculated_at',
    ];

    protected $casts = [
        'total_obtained' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'gpa' => 'decimal:2',
        'failed_subject_count' => 'integer',
        'is_pass' => 'boolean',
        'position' => 'integer',
        'manual_position' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function assessmentClass()
    {
        return $this->belongsTo(ExamAssessmentClass::class, 'assessment_class_id');
    }

    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }
}
