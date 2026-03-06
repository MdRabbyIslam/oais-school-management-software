<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamMarkComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_mark_id',
        'assessment_subject_component_id',
        'marks_obtained',
        'is_absent',
        'remarks',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'is_absent' => 'boolean',
    ];

    public function examMark()
    {
        return $this->belongsTo(ExamMark::class);
    }

    public function assessmentSubjectComponent()
    {
        return $this->belongsTo(ExamAssessmentSubjectComponent::class, 'assessment_subject_component_id');
    }
}

