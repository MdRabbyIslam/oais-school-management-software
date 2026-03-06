<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'dob',
        'blood_group',
        'primary_guardian_name',
        'primary_guardian_contact',
        'primary_guardian_relation',
        'secondary_guardian_name',
        'secondary_guardian_contact',
        'secondary_guardian_relation',
        'address',
        'admission_date',
        'roll_number',
        'section_id',
        'birth_certificate_path',
        'marksheet_path',
    ];

    protected static function booted()
    {
        static::creating(function (Student $student) {
            // Be defensive on null admission_date
            $year = (int) \Carbon\Carbon::parse($student->admission_date ?? now())->format('Y');

            // Per-year advisory lock key (string is fine)
            $lockKey = "student_id:{$year}";
            $timeoutSeconds = 5; // tweak if you expect spikes

            // Try to acquire the lock
            $gotLock = DB::selectOne('SELECT GET_LOCK(?, ? ) AS l', [$lockKey, $timeoutSeconds])->l ?? 0;

            if ((int)$gotLock !== 1) {
                // Couldn’t get the lock quickly — bail with a clear message
                throw new \RuntimeException('Could not acquire ID generator lock. Please retry.');
            }

            try {
                // Find the last ID for that year (lock held, so this is safe)
                $lastStudent = self::whereYear('admission_date', $year)
                    ->orderByDesc('student_id')   // OMS-YYYY-XYZ sorts correctly
                    ->first();

                $lastSerial = 0;
                if ($lastStudent && preg_match('/-(\d{3})$/', $lastStudent->student_id, $m)) {
                    $lastSerial = (int) $m[1];
                }

                $nextSerial = str_pad((string)($lastSerial + 1), 3, '0', STR_PAD_LEFT);
                $student->student_id = "OMS-{$year}-{$nextSerial}";
            } finally {
                // Always release the lock, even if something throws
                DB::select('SELECT RELEASE_LOCK(?)', [$lockKey]);
            }
        });


        // ==== UPDATING (new) ====
        static::updating(function (Student $student) {
            if (! $student->isDirty('admission_date')) {
                return; // admission date not changed → nothing to do
            }

            // derive old vs new year
            $oldAdmission = $student->getOriginal('admission_date');
            $newAdmission = $student->admission_date;

            // guard: if new date is null or unparsable, let validation layer handle it
            if (! $newAdmission) {
                return;
            }

            $oldYear = $oldAdmission ? (int) Carbon::parse($oldAdmission)->format('Y') : null;
            $newYear = (int) Carbon::parse($newAdmission)->format('Y');

            // if year didn’t change, keep existing student_id
            if ($oldYear === $newYear) {
                return;
            }

            // lock per new year so two updates don’t collide
            $lockKey = "student_id:{$newYear}";
            $timeoutSeconds = 5;

            $gotLock = DB::selectOne('SELECT GET_LOCK(?, ? ) AS l', [$lockKey, $timeoutSeconds])->l ?? 0;
            if ((int) $gotLock !== 1) {
                throw new \RuntimeException('Could not acquire ID generator lock for update. Please retry.');
            }

            try {
                $lastStudent = self::whereYear('admission_date', $newYear)
                    ->orderByDesc('student_id')
                    ->first();

                $lastSerial = 0;
                if ($lastStudent && preg_match('/-(\d+)$/', $lastStudent->student_id, $m)) {
                    $lastSerial = (int) $m[1];
                }

                $nextSerial = str_pad((string) ($lastSerial + 1), 3, '0', STR_PAD_LEFT);
                $student->student_id = "OMS-{$newYear}-{$nextSerial}";
            } finally {
                DB::select('SELECT RELEASE_LOCK(?)', [$lockKey]);
            }
        });


    }


    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function activeEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class)->where('status', 'active');
    }

    public function promotions()
    {
        return $this->hasMany(StudentPromotion::class);
    }

    // Defining the relationship: A Student can have many Fees through a many-to-many relationship
    public function fees()
    {
        return $this->belongsToMany(Fee::class, 'fee_assignments');
    }

    // A Student can have many FeeAssignments
    public function feeAssignments()
    {
        return $this->hasMany(FeeAssignment::class);
    }
   /**
     * Fee assignments tied to the student's active enrollment(s).
     */
    public function currentEnrollmentFeeAssignments()
    {
        return $this->feeAssignments()
            ->whereHas('studentEnrollment', function ($query) {
                $query->where('status', 'active');
            });
    }

    // a student with class relation
    public function schoolClass()
    {
        return $this->hasOneThrough(SchoolClass::class, Section::class, 'id', 'id', 'section_id', 'class_id');
    }

    public function invoices()
    {
        // assumes your invoices table has a student_id FK
        return $this->hasMany(Invoice::class, 'student_id');
    }

    // In your Student model
    public function activeEnrollments() // The proper relationship
    {
        return $this->hasMany(StudentEnrollment::class)->where('status', 'active');
    }

    // An Accessor to get the first one easily
    public function getSingleActiveEnrollmentAttribute()
    {
        // Accessing the relationship as a property loads it if not already loaded
        return $this->activeEnrollments->first();
    }


}
