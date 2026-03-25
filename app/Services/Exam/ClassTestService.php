<?php

namespace App\Services\Exam;

use App\Models\ClassTest;
use Illuminate\Support\Facades\DB;

class ClassTestService
{
    /**
     * @return array{created_count:int, created_tests:\Illuminate\Support\Collection<int, ClassTest>}
     */
    public function create(array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $status = $data['status'] ?? 'draft';
            $createForAllSubjects = (bool) ($data['create_for_all_subjects'] ?? false);

            unset($data['create_for_all_subjects']);

            if ($createForAllSubjects) {
                $subjectIds = DB::table('class_subject')
                    ->where('class_id', (int) $data['class_id'])
                    ->pluck('subject_id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

                $createdTests = collect();

                foreach ($subjectIds as $subjectId) {
                    $createdTests->push(ClassTest::create(array_merge($data, [
                        'subject_id' => $subjectId,
                        'created_by' => $createdBy,
                        'published_by' => $status === 'published' ? $createdBy : null,
                        'published_at' => $status === 'published' ? now() : null,
                    ])));
                }

                return [
                    'created_count' => $createdTests->count(),
                    'created_tests' => $createdTests,
                ];
            }

            $classTest = ClassTest::create(array_merge($data, [
                'created_by' => $createdBy,
                'published_by' => $status === 'published' ? $createdBy : null,
                'published_at' => $status === 'published' ? now() : null,
            ]));

            return [
                'created_count' => 1,
                'created_tests' => collect([$classTest]),
            ];
        });
    }

    public function update(ClassTest $classTest, array $data, int $updatedBy): ClassTest
    {
        return DB::transaction(function () use ($classTest, $data, $updatedBy) {
            $status = $data['status'] ?? $classTest->status;
            $publishPayload = [];

            if ($status === 'published') {
                $publishPayload = [
                    'published_by' => $updatedBy,
                    'published_at' => now(),
                ];
            } else {
                $publishPayload = [
                    'published_by' => null,
                    'published_at' => null,
                ];
            }

            $classTest->update(array_merge($data, $publishPayload));

            return $classTest->refresh();
        });
    }
}
