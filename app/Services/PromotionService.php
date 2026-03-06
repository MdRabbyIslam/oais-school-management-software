<?php

namespace App\Services;

use App\Models\StudentPromotion;
use App\Models\StudentEnrollment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /**
     * Request a promotion. Returns the created StudentPromotion.
     * Accepts array with keys: student_id, from_enrollment_id, target_academic_year_id, target_class_id, target_section_id, reason, requested_by_user_id, auto_promotion
     */
    public function request(array $data): StudentPromotion
    {
        return DB::transaction(function () use ($data) {
            $payload = array_merge([
                'status' => 'pending',
                'auto_promotion' => false,
            ], $data);

            $promotion = StudentPromotion::create($payload);

            return $promotion;
        });
    }

    /**
     * Create promotions in bulk. $rows is array of:
     * [student_id, from_enrollment_id, target_academic_year_id, target_class_id, target_section_id, reason|null]
     * Returns created collection.
     */
    public function bulkRequest(array $rows, int $requestedBy = null)
    {
        return DB::transaction(function () use ($rows, $requestedBy) {
            $created = [];
            foreach ($rows as $r) {
                $payload = [
                    'student_id' => $r['student_id'],
                    'from_enrollment_id' => $r['from_enrollment_id'],
                    'target_academic_year_id' => $r['target_academic_year_id'],
                    'target_class_id' => $r['target_class_id'],
                    'target_section_id' => $r['target_section_id'],
                    'reason' => $r['reason'] ?? null,
                    'requested_by_user_id' => $requestedBy,
                    'requested_at' => now(),
                    'auto_promotion' => $r['auto_promotion'] ?? false,
                    'status' => 'pending',
                ];

                $created[] = StudentPromotion::create($payload);
            }

            return collect($created);
        });
    }

    /**
     * Approve a promotion. Creates new enrollment, updates old enrollment and promotion.
     * $approverId optional user id who reviewed.
     */
    public function approve(StudentPromotion $promotion, int $approverId = null, array $opts = []): StudentPromotion
    {
        return DB::transaction(function () use ($promotion, $approverId, $opts) {
            $now = now();

            $from = StudentEnrollment::findOrFail($promotion->from_enrollment_id);

            // create new enrollment
            $newEnrollment = StudentEnrollment::create([
                'student_id' => $promotion->student_id,
                'academic_year_id' => $promotion->target_academic_year_id,
                'class_id' => $promotion->target_class_id,
                'section_id' => $promotion->target_section_id,
                'roll_number' => $opts['roll_number'] ?? null,
                'enrollment_date' => $opts['enrollment_date'] ?? $now->toDateString(),
                'status' => 'active',
            ]);

            // update old enrollment
            $from->status = 'promoted';
            $from->completion_date = $now->toDateString();
            $from->save();

            // update promotion
            $promotion->status = 'approved';
            $promotion->to_enrollment_id = $newEnrollment->id;
            $promotion->reviewed_by_user_id = $approverId;
            $promotion->reviewed_at = $now;
            $promotion->save();

            // update student current placement
            $student = Student::find($promotion->student_id);
            if ($student) {
                $student->section_id = $promotion->target_section_id;
                if (! empty($opts['roll_number'])) {
                    $student->roll_number = $opts['roll_number'];
                }
                if (array_key_exists('blood_group', $opts)) {
                    $student->blood_group = $opts['blood_group'];
                }
                $student->save();
            }

            return $promotion->refresh();
        });
    }

    /**
     * Reject a promotion with optional reason and reviewer.
     */
    public function reject(StudentPromotion $promotion, int $reviewerId = null, string $reason = null): StudentPromotion
    {
        $promotion->status = 'rejected';
        if ($reviewerId) $promotion->reviewed_by_user_id = $reviewerId;
        if ($reason) $promotion->reason = $reason;
        $promotion->reviewed_at = now();
        $promotion->save();
        return $promotion->refresh();
    }

    /**
     * Approve multiple promotions by ids. Returns collection of approved promotions.
     */
    public function bulkApprove(array $promotionIds, int $approverId = null)
    {
        return DB::transaction(function () use ($promotionIds, $approverId) {
            $approved = [];
            $promotions = StudentPromotion::whereIn('id', $promotionIds)
                ->where('status', 'pending')
                ->get();

            foreach ($promotions as $p) {
                $approved[] = $this->approve($p, $approverId);
            }

            return collect($approved);
        });
    }

    /**
     * Reject multiple promotions by ids. Returns collection of rejected promotions.
     */
    public function bulkReject(array $promotionIds, ?int $reviewerId, ?string $reason)
    {
        return DB::transaction(function () use ($promotionIds, $reviewerId, $reason) {
            $rejected = [];
            $promotions = StudentPromotion::whereIn('id', $promotionIds)
                ->where('status', 'pending')
                ->get();

            foreach ($promotions as $p) {
                $rejected[] = $this->reject($p, $reviewerId, $reason);
            }

            return collect($rejected);
        });
    }
}
