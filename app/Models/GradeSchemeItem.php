<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeSchemeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_scheme_id',
        'letter_grade',
        'gpa',
        'min_mark',
        'max_mark',
        'sort_order',
    ];

    protected $casts = [
        'gpa' => 'decimal:2',
        'min_mark' => 'decimal:2',
        'max_mark' => 'decimal:2',
    ];

    public function gradeScheme()
    {
        return $this->belongsTo(GradeScheme::class);
    }
}

