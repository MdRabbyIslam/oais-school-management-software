<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAssessmentSubjectComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_subject_id',
        'component_name',
        'component_code',
        'total_marks',
        'pass_marks',
        'sort_order',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
    ];

    public function assessmentSubject()
    {
        return $this->belongsTo(ExamAssessmentSubject::class, 'assessment_subject_id');
    }

    public function markComponents()
    {
        return $this->hasMany(ExamMarkComponent::class, 'assessment_subject_component_id');
    }
}

