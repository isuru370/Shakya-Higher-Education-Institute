<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\ClassSchedule;
use App\Models\ClassCategoryFee;
use App\Models\ClassSchedulePattern;
use Maatwebsite\Excel\Concerns\ToCollection;

class ClassSchedulesImport implements ToCollection
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

                $categoryFee = ClassCategoryFee::find(
                    $data['class_category_has_student_class_id']
                );

                if (!$categoryFee) {
                    DB::rollBack();
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Pattern
                |--------------------------------------------------------------------------
                */

                $pattern = ClassSchedulePattern::firstOrCreate(

                    [
                        'student_class_id' =>
                            $categoryFee->student_class_id,

                        'class_category_fee_id' =>
                            $categoryFee->id,

                        'class_day' =>
                            strtolower(
                                trim(
                                    $data['day_of_week']
                                )
                            ),
                    ],

                    [
                        'class_hall_id' =>
                            $data['class_hall_id'],

                        'start_date' =>
                            $data['date'],

                        'end_date' =>
                            $data['date'],

                        'start_time' =>
                            Carbon::parse(
                                $data['start_time']
                            )->format('H:i:s'),

                        'end_time' =>
                            Carbon::parse(
                                $data['end_time']
                            )->format('H:i:s'),

                        'is_active' => true,
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Update Pattern End Date
                |--------------------------------------------------------------------------
                */

                if (
                    Carbon::parse($data['date'])
                        ->gt(
                            Carbon::parse(
                                $pattern->end_date
                            )
                        )
                ) {

                    $pattern->update([
                        'end_date' =>
                            $data['date']
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Check
                |--------------------------------------------------------------------------
                */

                $exists = ClassSchedule::where(
                        'student_class_id',
                        $categoryFee->student_class_id
                    )
                    ->where(
                        'class_category_fee_id',
                        $categoryFee->id
                    )
                    ->whereDate(
                        'class_date',
                        $data['date']
                    )
                    ->where(
                        'start_time',
                        Carbon::parse(
                            $data['start_time']
                        )->format('H:i:s')
                    )
                    ->where(
                        'end_time',
                        Carbon::parse(
                            $data['end_time']
                        )->format('H:i:s')
                    )
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Schedule
                |--------------------------------------------------------------------------
                */

                ClassSchedule::create([

                    'class_schedule_pattern_id' =>
                        $pattern->id,

                    'student_class_id' =>
                        $categoryFee->student_class_id,

                    'class_category_fee_id' =>
                        $categoryFee->id,

                    'class_hall_id' =>
                        $data['class_hall_id'],

                    'class_date' =>
                        $data['date'],

                    'start_time' =>
                        Carbon::parse(
                            $data['start_time']
                        )->format('H:i:s'),

                    'end_time' =>
                        Carbon::parse(
                            $data['end_time']
                        )->format('H:i:s'),

                    'day_of_week' =>
                        strtolower(
                            trim(
                                $data['day_of_week']
                            )
                        ),

                    'status' =>
                        ((int)$data['status'] === 1)
                            ? 'completed'
                            : 'scheduled',

                    'is_active' =>
                        (bool)$data['is_ongoing'],

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
                    'Schedule Import Error',
                    [
                        'date' =>
                            $data['date'] ?? null,

                        'message' =>
                            $e->getMessage()
                    ]
                );
            }
        }
    }
}