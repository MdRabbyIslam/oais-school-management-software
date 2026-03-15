<?php

namespace App\Services\Exam;

use App\Models\ClassTest;
use Illuminate\Support\Facades\DB;

class ClassTestService
{
    public function create(array $data, int $createdBy): ClassTest
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $status = $data['status'] ?? 'draft';

            return ClassTest::create(array_merge($data, [
                'created_by' => $createdBy,
                'published_by' => $status === 'published' ? $createdBy : null,
                'published_at' => $status === 'published' ? now() : null,
            ]));
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
