<?php

namespace App\Imports;

use App\Models\StudentClass;
use App\Models\ClassPaymentConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentClassesImport implements ToCollection
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

                $studentClass = StudentClass::create([
                    'class_name' => $data['class_name'],
                    'class_type' => $data['class_type'],
                    'medium' => $data['medium'],
                    'teacher_id' => $data['teacher_id'],
                    'subject_id' => $data['subject_id'],
                    'grade_id' => $data['grade_id'],
                    'is_active' => $data['is_active'],
                    'is_ongoing' => $data['is_ongoing'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                ]);

                ClassPaymentConfig::create([
                    'student_class_id' => $studentClass->id,
                    'teacher_id' => $data['teacher_id'],

                    // default values
                    'organizer_id' => null,

                    'teacher_percentage' =>
                    $data['teacher_percentage'],

                    'organizer_percentage' => 0,

                    'institution_percentage' =>
                    100 - $data['teacher_percentage'],

                    'effective_from' => now(),

                    'effective_to' => null,

                    'is_active' => true,

                    'created_by' => 1,
                ]);

                DB::commit();
            } catch (\Exception $e) {

                DB::rollBack();

                logger()->error(
                    'Class Import Error',
                    [
                        'class_name' =>
                        $data['class_name'] ?? null,
                        'message' =>
                        $e->getMessage()
                    ]
                );
            }
        }
    }
}
