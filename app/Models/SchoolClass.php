<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'classes';
    protected $fillable = ['name', 'class_level'];

    public function sections()
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id');
    }
    public function subjectAssignments()
    {
        return $this->hasMany(SubjectAssignment::class, 'section_id', 'id');
    }

    public function gradingPolicies()
    {
        return $this->hasMany(GradingPolicy::class, 'class_id');
    }

    public function examAssessmentClasses()
    {
        return $this->hasMany(ExamAssessmentClass::class, 'class_id');
    }

    public function classTests()
    {
        return $this->hasMany(ClassTest::class, 'class_id');
    }
}
