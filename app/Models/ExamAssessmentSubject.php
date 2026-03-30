<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ExamAssessmentSubject stores per-subject setup for a class-wise assessment.
 *
 * Note on "weight":
 * - Defines how much this subject contributes in final aggregate calculation.
 * - 1.00 means full contribution, 0.50 means half contribution.
 * - Used by ExamResultService during final result aggregation.
 */
class ExamAssessmentSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_class_id',
        'subject_id',
        'grading_policy_id',
        'total_marks',
        'pass_marks',
        'exclude_from_final_gpa',
        'is_fourth_subject_eligible',
        'is_optional',
        'weight',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
        'exclude_from_final_gpa' => 'boolean',
        'is_fourth_subject_eligible' => 'boolean',
        'is_optional' => 'boolean',
        // Stored as decimal to support fractional weighting (e.g., 0.50).
        'weight' => 'decimal:2',
    ];

    public function assessmentClass()
    {
        return $this->belongsTo(ExamAssessmentClass::class, 'assessment_class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function gradingPolicy()
    {
        return $this->belongsTo(GradingPolicy::class);
    }

    public function components()
    {
        return $this->hasMany(ExamAssessmentSubjectComponent::class, 'assessment_subject_id');
    }

    public function marks()
    {
        return $this->hasMany(ExamMark::class, 'assessment_subject_id');
    }
}
