<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'total_marks',
        'pass_marks',
        'grade_scheme_id',
        'is_optional',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_optional' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function gradeScheme()
    {
        return $this->belongsTo(GradeScheme::class);
    }

    public function examAssessmentSubjects()
    {
        return $this->hasMany(ExamAssessmentSubject::class);
    }

    public function components()
    {
        return $this->hasMany(GradingPolicyComponent::class)->orderBy('sort_order');
    }
}
