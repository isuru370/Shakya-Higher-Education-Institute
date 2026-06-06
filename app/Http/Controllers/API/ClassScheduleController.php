<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StudentClass;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function todayClasses(Request $request)
    {
        $today = Carbon::today();
        $search = trim($request->input('search', ''));

        $classes = StudentClass::query()
            ->select([
                'id',
                'class_name',
                'class_type',
                'medium',
                'grade_id',
                'subject_id',
                'teacher_id',
                'is_active',
                'is_ongoing',
            ])
            ->with([
                'grade:id,grade_name',
                'subject:id,subject_name',
                'teacher:id,full_name,mobile',

                'categoryFees' => function ($query) {
                    $query->select([
                        'id',
                        'student_class_id',
                        'class_category_id',
                        'fee',
                        'is_active',
                    ])
                    ->where('is_active', true)
                    ->with([
                        'category:id,category_name',
                    ]);
                },

                'schedules' => function ($query) use ($today) {
                    $query->select([
                        'id',
                        'student_class_id',
                        'class_category_fee_id',
                        'class_schedule_pattern_id',
                        'class_date',
                        'start_time',
                        'end_time',
                        'status',
                        'class_hall_id',
                    ])
                    ->whereDate('class_date', $today)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->with([
                        'hall:id,hall_name,code',
                    ])
                    ->orderBy('start_time');
                },
            ])
            ->where('is_active', true)
            ->where('is_ongoing', true)
            ->whereHas('schedules', function ($query) use ($today) {
                $query->whereDate('class_date', $today)
                    ->whereNotIn('status', ['cancelled', 'completed']);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('class_name', 'like', "%{$search}%")
                        ->orWhere('class_type', 'like', "%{$search}%")
                        ->orWhere('medium', 'like', "%{$search}%")
                        ->orWhereHas('grade', function ($grade) use ($search) {
                            $grade->where('grade_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subject', function ($subject) use ($search) {
                            $subject->where('subject_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('teacher', function ($teacher) use ($search) {
                            $teacher->where('full_name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        })
                        ->orWhereHas('categoryFees.category', function ($category) use ($search) {
                            $category->where('category_name', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        $data = $classes->flatMap(function ($class) {
            return $class->categoryFees->flatMap(function ($fee) use ($class) {
                $matchedSchedules = $class->schedules->where('class_category_fee_id', $fee->id);

                return $matchedSchedules->map(function ($schedule) use ($class, $fee) {
                    return [
                        'student_class' => [
                            'id' => $class->id,
                            'class_name' => $class->class_name,
                            'class_type' => $class->class_type,
                            'medium' => $class->medium,
                            'grade' => $class->grade ? [
                                'id' => $class->grade->id,
                                'grade_name' => $class->grade->grade_name,
                            ] : null,
                            'subject' => $class->subject ? [
                                'id' => $class->subject->id,
                                'subject_name' => $class->subject->subject_name,
                            ] : null,
                            'teacher' => $class->teacher ? [
                                'id' => $class->teacher->id,
                                'full_name' => $class->teacher->full_name,
                                'mobile' => $class->teacher->mobile,
                            ] : null,
                        ],
                        'category_fee' => [
                            'id' => $fee->id,
                            'class_category_id' => $fee->class_category_id,
                            'fee' => $fee->fee,
                            'category' => $fee->category ? [
                                'id' => $fee->category->id,
                                'category_name' => $fee->category->category_name,
                            ] : null,
                        ],
                        'schedule' => [
                            'id' => $schedule->id,
                            'class_category_fee_id' => $schedule->class_category_fee_id,
                            'class_schedule_pattern_id' => $schedule->class_schedule_pattern_id,
                            'class_date' => $schedule->class_date,
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                            'status' => $schedule->status,
                            'hall' => $schedule->hall ? [
                                'id' => $schedule->hall->id,
                                'hall_name' => $schedule->hall->hall_name,
                                'code' => $schedule->hall->code,
                            ] : null,
                        ],
                    ];
                });
            });
        })->values();

        $data = $data->sortBy(function ($row) {
            return $row['schedule']['start_time'] ?? '';
        })->values();

        return response()->json([
            'success' => true,
            'date' => $today->toDateString(),
            'count' => $data->count(),
            'data' => $data,
        ]);
    }
}