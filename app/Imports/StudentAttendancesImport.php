<?php

namespace App\Imports;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use App\Models\StudentAttendance;
use App\Models\ClassSchedule;
use App\Models\StudentClassEnrollment;

use Maatwebsite\Excel\Concerns\ToCollection;

class StudentAttendancesImport implements ToCollection
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
                | Old attendance id => New class schedule id
                |--------------------------------------------------------------------------
                */

                $schedule = ClassSchedule::find(
                    $data['attendance_id']
                );

                if (!$schedule) {
                    DB::rollBack();
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Enrollment
                |--------------------------------------------------------------------------
                */

                $enrollment =
                    StudentClassEnrollment::where(
                        'student_id',
                        $data['student_id']
                    )
                    ->where(
                        'student_class_id',
                        $schedule->student_class_id
                    )
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Duplicate
                |--------------------------------------------------------------------------
                */

                $exists =
                    StudentAttendance::where(
                        'student_id',
                        $data['student_id']
                    )
                    ->where(
                        'class_schedule_id',
                        $schedule->id
                    )
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    continue;
                }

                StudentAttendance::create([

                    'local_uuid' =>
                    (string) Str::uuid(),

                    'student_id' =>
                    $data['student_id'],

                    'class_schedule_id' =>
                    $schedule->id,

                    'student_class_enrollment_id' =>
                    $enrollment?->id,

                    'attended_at' =>
                    $data['at_date'],

                    'mark_method' =>
                    'qr_web',

                    'marked_by' => 1,

                    'is_synced' => true,

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
                    'Attendance Import Error',
                    [
                        'student_id' =>
                        $data['student_id'] ?? null,

                        'attendance_id' =>
                        $data['attendance_id'] ?? null,

                        'message' =>
                        $e->getMessage()
                    ]
                );
            }
        }
    }
}
