<?php

namespace App\Imports;

use App\Models\ClassCategoryFee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ClassCategoryFeesImport implements ToCollection
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

                // duplicate skip
                if (
                    ClassCategoryFee::where(
                        'student_class_id',
                        $data['student_classes_id']
                    )
                    ->where(
                        'class_category_id',
                        $data['class_category_id']
                    )
                    ->exists()
                ) {
                    DB::rollBack();
                    continue;
                }

                ClassCategoryFee::create([

                    'student_class_id' =>
                    $data['student_classes_id'],

                    'class_category_id' =>
                    $data['class_category_id'],

                    'fee' =>
                    $data['fees'],

                    'is_active' => true,

                    'note' => null,

                    'created_at' =>
                    $data['created_at'],

                    'updated_at' =>
                    $data['updated_at'],
                ]);

                DB::commit();
            } catch (\Exception $e) {

                DB::rollBack();

                logger()->error(
                    'Class Category Fee Import Error',
                    [
                        'student_class_id' =>
                        $data['student_classes_id'] ?? null,

                        'message' =>
                        $e->getMessage()
                    ]
                );
            }
        }
    }
}
