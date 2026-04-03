<?php

namespace App\Services;

use App\Jobs\SendPaymentSms;
use App\Models\ClassAttendance;
use App\Models\Student;
use App\Models\StudentStudentStudentClass;
use App\Models\ClassCategoryHasStudentClass;
use App\Models\Payments;
use App\Models\StudentAttendance;
use App\Models\Titute;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class StudentAttendanceService
{
    public function readAttendance(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        try {
            $qrCode = trim($request->qr_code);
            $now = Carbon::now();

            // 1. Find student by temporary or permanent QR
            if (str_starts_with($qrCode, 'TMP')) {
                $student = Student::where('temporary_qr_code', $qrCode)
                    ->where('student_disable', false)
                    ->first();

                if (!$student) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Temporary QR code invalid',
                        'data' => []
                    ], 404);
                }

                if (
                    $student->temporary_qr_code_expire_date &&
                    $now->gt($student->temporary_qr_code_expire_date)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Temporary QR code has expired',
                        'data' => []
                    ], 403);
                }
            } else {
                $student = Student::where('custom_id', $qrCode)
                    ->where('student_disable', false)
                    ->first();

                if (!$student) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'QR code invalid',
                        'data' => []
                    ], 404);
                }

                if (!$student->permanent_qr_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Permanent QR code is inactive',
                        'data' => []
                    ], 403);
                }
            }

            // 2. Student active check
            if ((int) $student->is_active === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student is inactive',
                    'data' => []
                ], 403);
            }

            // 3. Get valid class/session details
            $result = $this->getStudentClassesDetails($student->id);

            if (empty($result)) {
                return response()->json([
                    'status' => 'empty',
                    'message' => 'No valid ongoing Theory or Revision classes found for this student',
                    'student_id' => $student->id,
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Student attendance data fetched successfully',
                'student_id' => $student->id,
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch student attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentClassesDetails($student_id)
    {
        if (!$student_id) {
            return [];
        }

        $now = Carbon::now();
        $today = $now->toDateString();

        $enrollments = StudentStudentStudentClass::with([
            'studentClass',
            'student',
            'classCategoryHasStudentClass.classCategory'
        ])
            ->where('student_id', $student_id)
            ->where('status', true)
            ->get();

        if ($enrollments->isEmpty()) {
            return [];
        }

        $result = [];

        foreach ($enrollments as $enrollment) {
            $studentClass = $enrollment->studentClass;
            $student = $enrollment->student;
            $categoryLink = $enrollment->classCategoryHasStudentClass;
            $packageCategoryName = optional(optional($categoryLink)->classCategory)->category_name;

            if (!$studentClass || !$student || !$categoryLink || !$packageCategoryName) {
                continue;
            }

            $todaysClasses = ClassAttendance::with([
                'hall',
                'classCategoryStudentClass.classCategory'
            ])
                ->whereDate('date', $today)
                ->whereHas('classCategoryStudentClass', function ($q) use ($studentClass) {
                    $q->where('student_classes_id', $studentClass->id);
                })
                ->get();

            foreach ($todaysClasses as $todaysClass) {
                $sessionCategoryName = optional(optional($todaysClass->classCategoryStudentClass)->classCategory)->category_name;

                if (!$sessionCategoryName) {
                    continue;
                }

                if (str_contains($sessionCategoryName, '+')) {
                    continue;
                }

                $allowed = false;

                if ($sessionCategoryName === 'Theory') {
                    $allowed = in_array($packageCategoryName, ['Theory', 'Theory+Revision'], true);
                } elseif ($sessionCategoryName === 'Revision') {
                    $allowed = in_array($packageCategoryName, ['Revision', 'Theory+Revision'], true);
                }

                if (!$allowed) {
                    continue;
                }

                $cleanDate = Carbon::parse($todaysClass->date)->format('Y-m-d');

                $start = $this->parseDateTime($cleanDate, $todaysClass->start_time);
                $end   = $this->parseDateTime($cleanDate, $todaysClass->end_time);

                if (!$start || !$end) {
                    continue;
                }

                $oneHourBefore = $start->copy()->subHour();

                if (!$now->between($oneHourBefore, $end)) {
                    continue;
                }

                $alreadyMarked = StudentAttendance::where('student_id', $student_id)
                    ->where('attendance_id', $todaysClass->id)
                    ->exists();

                $attendanceCountThisMonth = StudentAttendance::where('student_id', $student_id)
                    ->whereBetween('at_date', [
                        $now->copy()->startOfMonth(),
                        $now->copy()->endOfMonth()
                    ])
                    ->count();

                $attendanceCountForThisClass = StudentAttendance::where('student_id', $student_id)
                    ->where('attendance_id', $todaysClass->id)
                    ->whereBetween('at_date', [
                        $now->copy()->startOfMonth(),
                        $now->copy()->endOfMonth()
                    ])
                    ->count();

                $lastPaymentRecord = Payments::where('student_id', $student_id)
                    ->latest()
                    ->first();

                $thisMonthAlreadyTute = Titute::where('student_id', $student_id)
                    ->where('class_category_has_student_class_id', $todaysClass->class_category_has_student_class_id)
                    ->where('titute_for', $now->format('M Y'))
                    ->exists();

                $result[] = [
                    'category_name' => $sessionCategoryName,
                    'student_package' => $packageCategoryName,
                    'student_class_name' => $studentClass->class_name ?? 'N/A',

                    'studentStudentStudentClass' => [
                        'student_student_student_class_id' => $enrollment->id,
                        'student_class_status' => (bool) $enrollment->status,
                        'is_free_card' => (bool) $enrollment->is_free_card,
                        'custom_fee' => $enrollment->custom_fee,
                        'discount_percentage' => $enrollment->discount_percentage,
                        'discount_type' => $enrollment->discount_type,
                        'default_fee' => $enrollment->default_fee ?? null,
                        'final_fee' => $enrollment->final_fee ?? null,
                    ],

                    'student' => [
                        'id' => $student_id,
                        'img_url' => $student->img_url ?? null,
                        'custom_id' => $student->custom_id ?? null,
                        'first_name' => $student->full_name ?? null,
                        'last_name' => $student->initial_name ?? null,
                        'guardian_mobile' => $student->guardian_mobile ?? null,
                    ],

                    'ongoing_class' => [
                        'id' => $todaysClass->id,
                        'class_category_has_student_class_id' => $todaysClass->class_category_has_student_class_id,
                        'start_time' => $todaysClass->start_time,
                        'end_time' => $todaysClass->end_time,
                        'class_hall_id' => $todaysClass->class_hall_id,
                        'class_hall_name' => optional($todaysClass->hall)->hall_name,
                        'class_hall_price' => optional($todaysClass->hall)->hall_price,
                        'date' => Carbon::parse($todaysClass->date)->format('Y-m-d'),
                        'is_ongoing' => $todaysClass->is_ongoing,
                        'current_time' => $now->format('h:i A'),
                        'already_marked' => $alreadyMarked,
                    ],

                    'payment_info' => $lastPaymentRecord ? [
                        'last_payment_date' => optional($lastPaymentRecord->created_at)->format('Y-m-d'),
                        'payment_for' => $lastPaymentRecord->payment_for,
                        'last_payment_amount' => $lastPaymentRecord->amount,
                        'payment_status' => $lastPaymentRecord->status,
                    ] : null,

                    'tute_info' => [
                        'has_tute_for_this_month' => $thisMonthAlreadyTute,
                        'current_month' => $now->format('F Y'),
                    ],

                    'attendance_info' => [
                        'attendance_count_this_month_total' => $attendanceCountThisMonth,
                        'attendance_count_for_this_class' => $attendanceCountForThisClass,
                        'current_month' => $now->format('F Y'),
                    ]
                ];
            }
        }

        return collect($result)
            ->unique(function ($item) {
                return $item['ongoing_class']['id'] . '-' . $item['student']['id'];
            })
            ->values()
            ->all();
    }

    private function parseDateTime(string $date, string $timeString): ?Carbon
    {
        $time = trim($timeString);

        // Parse formats like: "2 PM", "2:00 PM", "14:00", "2.00 PM"
        $formats = ['h:i A', 'h A', 'H:i', 'h.i A'];

        foreach ($formats as $f) {
            try {
                return Carbon::createFromFormat('Y-m-d ' . $f, $date . ' ' . $time);
            } catch (Exception $e) {
                // try next format
            }
        }

        return null;
    }

    // ================================
    // STORE ATTENDANCE
    // ================================
    public function storeAttendance(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'student_id' => 'required|integer|exists:students,id',
                'attendance_id' => 'required|integer|exists:class_attendances,id',
                'at_date' => 'nullable|date',
                'tute' => 'required|boolean',
                'class_category_has_student_class_id' => 'nullable|integer|exists:class_category_has_student_class,id',
                'guardian_mobile' => 'nullable|string',
            ]);

            $studentId = (int) $validated['student_id'];
            $attendanceId = (int) $validated['attendance_id'];
            $atDate = !empty($validated['at_date']) ? Carbon::parse($validated['at_date']) : now();
            $markTute = (bool) $validated['tute'];
            $classCategoryHasStudentClassId = $validated['class_category_has_student_class_id'] ?? null;

            // Duplicate check
            $exists = StudentAttendance::where('student_id', $studentId)
                ->where('attendance_id', $attendanceId)
                ->exists();

            if ($exists) {
                DB::rollBack();

                return response()->json([
                    'status' => 'duplicate',
                    'message' => 'Attendance already marked for this session',
                    'attendance_marked' => false,
                    'tute_marked' => false,
                ], 409);
            }

            // Store attendance
            $attendance = StudentAttendance::create([
                'student_id' => $studentId,
                'attendance_id' => $attendanceId,
                'at_date' => $atDate,
            ]);

            $attendanceMarked = true;
            $tuteMarked = false;

            if ($attendanceMarked) {
                $this->classAttendanceStatusUpdate($attendanceId);
            }

            // Store tute if frontend says true
            if ($markTute && $classCategoryHasStudentClassId) {
                $month = now()->startOfMonth();

                $tuteExists = Titute::where('student_id', $studentId)
                    ->where('class_category_has_student_class_id', $classCategoryHasStudentClassId)
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->exists();

                if (!$tuteExists) {
                    Titute::create([
                        'student_id' => $studentId,
                        'class_category_has_student_class_id' => $classCategoryHasStudentClassId,
                        'titute_for' => $month->format('M Y'),
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $tuteMarked = true;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance marked successfully',
                'attendance_marked' => $attendanceMarked,
                'tute_marked' => $tuteMarked,
                'data' => [
                    'id' => $attendance->id,
                    'student_id' => $attendance->student_id,
                    'attendance_id' => $attendance->attendance_id,
                    'at_date' => $attendance->at_date,
                ]
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while saving attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update the class attendance status after student attendance is added
     */
    private function classAttendanceStatusUpdate($class_attendance_id)
    {
        if ($class_attendance_id) {
            $classAttendance = ClassAttendance::find($class_attendance_id);
            if ($classAttendance) {
                // Example: mark as ongoing (1) if not already
                $classAttendance->status = "1";
                $classAttendance->save();
            }
        }
    }

    // ================================
    // Frtch All ATTENDANCE
    // ================================
    public function getAllAttendances(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'student_student_student_classes_id' => 'required|integer'
        ]);

        $student_id = $request->student_id;
        $student_student_student_classes_id = $request->student_student_student_classes_id;

        try {
            $records = StudentAttendance::with([
                'student',
                'studentStudentClass.studentClass' // use camelCase function name
            ])
                ->where('student_id', $student_id)
                ->where('student_student_student_classes_id', $student_student_student_classes_id)
                ->orderBy('at_date', 'asc')
                ->get();



            return response()->json([
                'status' => 'success',
                'total_records' => $records->count(),
                'data' => $records
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch attendance records.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentAttendances($studentId, $classCategoryHasStudentClassId)
    {
        try {
            // Check whether student is enrolled in this class
            StudentStudentStudentClass::where('student_id', $studentId)
                ->where('class_category_has_student_class_id', $classCategoryHasStudentClassId)
                ->firstOrFail();

            // Get all active ongoing class attendances
            $classAttendances = ClassAttendance::where('class_category_has_student_class_id', $classCategoryHasStudentClassId)
                ->where('status', 1)
                ->where('is_ongoing', 1)
                ->orderBy('date', 'asc')
                ->get();

            $attendanceIds = $classAttendances->pluck('id');

            // Get student's present attendance ids
            $presentAttendanceIds = StudentAttendance::where('student_id', $studentId)
                ->whereIn('attendance_id', $attendanceIds)
                ->pluck('attendance_id')
                ->toArray();

            $attendanceData = [];
            $presentCount = 0;

            foreach ($classAttendances as $attendance) {
                $isPresent = in_array($attendance->id, $presentAttendanceIds);

                if ($isPresent) {
                    $presentCount++;
                }

                $attendanceData[] = [
                    'date' => $attendance->date,
                    'status' => $isPresent ? 'Present' : 'Absent',
                ];
            }

            $totalClasses = $classAttendances->count();
            $percentage = $totalClasses > 0
                ? round(($presentCount / $totalClasses) * 100, 2)
                : 0;

            return response()->json([
                'status' => 'success',
                'total_records' => $totalClasses,
                'present_count' => $presentCount,
                'attendance_percentage' => $percentage,
                'data' => $attendanceData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch attendance records.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    // ================================
    // UPDATE ATTENDANCE
    // ================================
    public function updateAttendance(Request $request, $id)
    {
        try {

            // Validate input
            $request->validate([
                'student_id' => 'required|integer',
                'student_student_student_classes_id' => 'required|integer',
                'status' => 'required|integer' // status = class_attendance id
            ]);

            $attendance = StudentAttendance::find($id);

            if (!$attendance) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Attendance record not found.'
                ], 404);
            }

            $date = $attendance->at_date;

            // Check duplicate except current record
            $duplicate = StudentAttendance::whereDate('at_date', $date)
                ->where('student_id', $request->student_id)
                ->where('student_student_student_classes_id', $request->student_student_student_classes_id)
                ->where('status', $request->status)
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'status' => 'duplicate',
                    'message' => 'Another attendance record already exists for this student in this class with the same status on this date.'
                ], 409);
            }

            // Update record
            $attendance->update([
                'student_id' => $request->student_id,
                'student_student_student_classes_id' => $request->student_student_student_classes_id,
                'status' => $request->status
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance updated successfully!',
                'data' => $attendance
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while updating attendance. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function monthStudentAttendanceCount($student_id, $student_class_id, $yearMonth)
    {
        try {
            [$year, $month] = explode('-', $yearMonth);

            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end   = Carbon::create($year, $month, 1)->endOfMonth();

            // Attendance Count
            $count = StudentAttendance::where('student_id', $student_id)
                ->where('student_student_student_classes_id', $student_class_id)
                ->whereBetween('at_date', [$start, $end])
                ->count();

            // Number of days in this month
            $daysInMonth = $start->daysInMonth;

            // Calculate number of weeks in this month
            $weeksInMonth = (int) ceil($daysInMonth / 7);

            return response()->json([
                'status' => 'success',
                'message' => 'Monthly attendance count fetched successfully',
                'data' => [
                    'student_id' => $student_id,
                    'student_class_id' => $student_class_id,
                    'year_month' => $yearMonth,
                    'attendance_count' => $count,
                    'weeks_in_month' => $weeksInMonth
                ]
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch monthly attendance count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function studentAttendClass($class_id, $attendance_id, $class_category_student_class_id)
    {
        try {
            if (is_null($class_id) || is_null($attendance_id) || is_null($class_category_student_class_id)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'One or more required parameters are missing.',
                ], 400);
            }

            // Get grouped students
            $processedData = $this->processStudentCategories($class_id);

            $matchedGroup = collect($processedData['grouped_results'])
                ->firstWhere('class_category_has_student_class_id', $class_category_student_class_id);

            if (!$matchedGroup) {
                return response()->json([
                    'status' => false,
                    'message' => 'No matching category group found.',
                ], 404);
            }

            $studentIds = collect($matchedGroup['students'])
                ->pluck('student_id')
                ->unique()
                ->values()
                ->toArray();

            $today = now()->toDateString();

            // 🔥 FIX: only valid columns used
            $attendanceRecords = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('at_date', $today)
                ->where('attendance_id', $attendance_id)
                ->get()
                ->keyBy('student_id');

            $attendanceList = [];

            foreach ($studentIds as $studentId) {
                if (isset($attendanceRecords[$studentId])) {
                    $record = $attendanceRecords[$studentId];

                    $attendanceList[] = [
                        'student_id' => $studentId,
                        'attendance_status' => 'present',
                        'status' => 1, // ✅ FIXED (no DB column)
                        'attendance_id' => $record->id, // student_attendance id
                        'attendance_date' => $record->at_date,
                        'is_present' => true,
                    ];
                } else {
                    $attendanceList[] = [
                        'student_id' => $studentId,
                        'attendance_status' => 'absent',
                        'status' => 0,
                        'attendance_id' => null,
                        'attendance_date' => $today,
                        'is_present' => false,
                    ];
                }
            }

            // Student details
            $students = Student::whereIn('id', array_column($attendanceList, 'student_id'))
                ->get(['id', 'custom_id', 'full_name', 'initial_name', 'guardian_mobile'])
                ->keyBy('id');

            $finalResult = [];

            foreach ($attendanceList as $entry) {
                $student = $students[$entry['student_id']] ?? null;

                $finalResult[] = [
                    'student_id' => $entry['student_id'],
                    'custom_id' => $student->custom_id ?? null,
                    'fname' => $student->full_name ?? null,
                    'lname' => $student->initial_name ?? null,
                    'guardian_mobile' => $student->guardian_mobile ?? null,
                    'attendance_status' => $entry['attendance_status'],
                    'status' => $entry['status'],
                    'attendance_id' => $entry['attendance_id'],
                    'attendance_date' => $entry['attendance_date'],
                    'is_present' => $entry['is_present'],
                ];
            }

            $presentCount = count(array_filter($finalResult, fn($x) => $x['is_present']));
            $absentCount = count(array_filter($finalResult, fn($x) => !$x['is_present']));

            return response()->json([
                'status' => true,
                'data' => [
                    'matched_group' => [
                        'class_category_has_student_class_id' => $matchedGroup['class_category_has_student_class_id'],
                        'category_name' => $matchedGroup['category_name'],
                    ],
                    'attendance_list' => $finalResult,
                    'summary' => [
                        'date' => $today,
                        'requested_status' => $attendance_id,
                        'total_students' => count($studentIds),
                        'present' => $presentCount,
                        'absent' => $absentCount,
                        'attendance_percentage' =>
                        count($studentIds) > 0
                            ? round(($presentCount / count($studentIds)) * 100, 2)
                            : 0,
                    ],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    private function processStudentCategories($class_id)
    {
        // 1. Get all students in the class and their categories
        $students = StudentStudentStudentClass::with([
            'classCategoryHasStudentClass',
            'classCategoryHasStudentClass.classCategory'
        ])
            ->where('student_classes_id', $class_id)
            ->where('status', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'class_category_has_student_class_id' => $item->class_category_has_student_class_id,
                    'category_name' => $item->classCategoryHasStudentClass->classCategory->category_name ?? null,
                    'student_id' => $item->student_id,
                ];
            });

        // 2. Extract unique category names
        $studentCategories = $students->pluck('category_name')->unique()->filter()->toArray();

        // 3. Split combined categories such as "Theory+Revision"
        $individualCategories = [];

        foreach ($studentCategories as $category) {
            if (strpos($category, '+') !== false) {
                foreach (explode('+', $category) as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $individualCategories[] = $part;
                    }
                }
            } else {
                $individualCategories[] = $category;
            }
        }

        $individualCategories = array_unique($individualCategories);

        // 4. Get single (non-combined) categories from DB
        $classCategories = ClassCategoryHasStudentClass::with('classCategory')
            ->where('student_classes_id', $class_id)
            ->get()
            ->filter(fn($item) => strpos($item->classCategory->category_name ?? '', '+') === false)
            ->map(function ($item) {
                return [
                    'class_category_has_student_class_id' => $item->id,
                    'category_name' => $item->classCategory->category_name ?? null,
                ];
            });

        // 5. Match student category names with real category IDs
        $matchedCategories = [];

        foreach ($individualCategories as $studentCat) {
            foreach ($classCategories as $classCat) {
                if ($studentCat === $classCat['category_name']) {
                    $matchedCategories[] = [
                        'student_category' => $studentCat,
                        'class_category_has_student_class_id' => $classCat['class_category_has_student_class_id'],
                        'category_name' => $classCat['category_name'],
                    ];
                    break;
                }
            }
        }

        // 6. Group students by matched category
        $groupedResults = [];

        foreach ($matchedCategories as $matched) {
            $categoryId = $matched['class_category_has_student_class_id'];

            $categoryStudents = $students->filter(function ($student) use ($matched, $categoryId) {
                $catName = $student['category_name'];

                if (strpos($catName, '+') !== false) {
                    $parts = array_map('trim', explode('+', $catName));
                    return in_array($matched['student_category'], $parts);
                }

                return $student['class_category_has_student_class_id'] == $categoryId;
            })->values();

            $groupedResults[] = [
                'class_category_has_student_class_id' => $categoryId,
                'category_name' => $matched['category_name'],
                'students' => $categoryStudents,
                'student_count' => $categoryStudents->count(),
            ];
        }

        return [
            'all_students' => $students,
            'student_categories' => $studentCategories,
            'individual_student_categories' => array_values($individualCategories),
            'class_categories_without_combined' => $classCategories,
            'matched_categories' => $matchedCategories,
            'grouped_results' => $groupedResults,
        ];
    }

    public function attendanceRecoadDelete($id)
    {
        try {
            // Find the attendance record
            $attendance = StudentAttendance::find($id);

            if (!$attendance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attendance record not found'
                ], 404);
            }

            // Delete the attendance record
            $attendance->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance record deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete attendance record',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
