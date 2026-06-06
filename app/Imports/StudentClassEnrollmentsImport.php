<?php

namespace App\Imports;

use App\Models\StudentClassEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentClassEnrollmentsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $header = $rows->shift();

        foreach ($rows as $row) {

            $data = array_combine(
                $header->toArray(),
                $row->toArray()
            );

            try {

                DB::beginTransaction();

                /*
                |--------------------------------------------------------------------------
                | Duplicate check
                |--------------------------------------------------------------------------
                */

                $exists =
                    StudentClassEnrollment::where(
                        'student_id',
                        $data['student_id']
                    )
                    ->where(
                        'student_class_id',
                        $data['student_classes_id']
                    )
                    ->where(
                        'class_category_fee_id',
                        $data['class_category_has_student_class_id']
                    )
                    ->exists();

                if ($exists) {

                    logger()->info(
                        'Duplicate Enrollment Skipped',
                        [
                            'student_id' =>
                            $data['student_id'],

                            'student_class_id' =>
                            $data['student_classes_id'],

                            'class_category_fee_id' =>
                            $data['class_category_has_student_class_id'],

                            'status' =>
                            $data['status'] ?? null,
                        ]
                    );

                    DB::rollBack();
                    continue;
                }

                $enrollment = StudentClassEnrollment::create([

                    'student_id' =>
                    $data['student_id'],

                    'student_class_id' =>
                    $data['student_classes_id'],

                    'class_category_fee_id' =>
                    $data['class_category_has_student_class_id'],

                    /*
                    |--------------------------------------------------------------------------
                    | Status Mapping
                    |--------------------------------------------------------------------------
                    */

                    'is_active' => ((int)($data['status'] ?? 0) === 1),

                    'is_free_card' =>
                    !empty($data['is_free_card'])
                        ? (bool)$data['is_free_card']
                        : false,

                    'custom_fee' => (
                        !empty($data['custom_fee']) &&
                        strtoupper(trim($data['custom_fee'])) !== 'NULL'
                    )
                        ? (float)$data['custom_fee']
                        : null,

                    'custom_fee_reason' => null,

                    'discount_percentage' => (
                        !empty($data['discount_percentage']) &&
                        strtoupper(trim($data['discount_percentage'])) !== 'NULL'
                    )
                        ? (float)$data['discount_percentage']
                        : null,

                    'discount_reason' =>
                    !empty($data['discount_type'])
                        ? $data['discount_type']
                        : null,

                    'enrolled_at' =>
                    !empty($data['created_at'])
                        ? date(
                            'Y-m-d',
                            strtotime($data['created_at'])
                        )
                        : null,

                    'left_at' => null,

                    'note' => null,

                    'created_at' =>
                    $data['created_at'],

                    'updated_at' =>
                    $data['updated_at'],
                ]);

                logger()->info(
                    'Enrollment Imported',
                    [
                        'enrollment_id' =>
                        $enrollment->id,

                        'student_id' =>
                        $data['student_id'],

                        'student_class_id' =>
                        $data['student_classes_id'],

                        'class_category_fee_id' =>
                        $data['class_category_has_student_class_id'],

                        'status' =>
                        $data['status'] ?? null,

                        'is_active' => ((int)($data['status'] ?? 0) === 1),
                    ]
                );

                DB::commit();
            } catch (\Exception $e) {

                DB::rollBack();

                logger()->error(
                    'Enrollment Import Error',
                    [
                        'student_id' =>
                        $data['student_id'] ?? null,

                        'student_class_id' =>
                        $data['student_classes_id'] ?? null,

                        'class_category_fee_id' =>
                        $data['class_category_has_student_class_id'] ?? null,

                        'message' =>
                        $e->getMessage()
                    ]
                );
            }
        }
    }
}
