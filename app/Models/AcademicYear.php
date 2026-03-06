<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $table = 'academic_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_admissions_open'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_admissions_open' => 'boolean',
    ];

    /**
     * Get the current academic year.
     *
     * @return AcademicYear|null
     */
    public static function current()
    {
        return self::where('is_current', true)->first();
    }
    /**
     * Set the current academic year.
     *
     * @param AcademicYear $academicYear
     * @return void
     */
    public static function setCurrent(AcademicYear $academicYear)
    {
        self::where('is_current', true)->update(['is_current' => false]);
        $academicYear->is_current = true;
        $academicYear->save();
    }

    /**
     * Get the terms for the academic year.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function terms()
    {
        return $this->hasMany(Term::class);
    }

    /**
     * Get student enrollments for the academic year.
     */
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get promotions targeting this academic year.
     */
    public function promotions()
    {
        return $this->hasMany(StudentPromotion::class, 'target_academic_year_id');
    }
}
