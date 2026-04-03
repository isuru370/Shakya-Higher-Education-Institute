<?php

namespace App\Services;

use App\Models\ClassCategoryHasStudentClass;
use App\Models\Student;
use App\Models\StudentStudentStudentClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class StudentStudentStudentClassService
{

    public function readStudentClass(Request $request)
    {
        try {
            $validated = $request->validate([
                'qr_code' => 'required|string',
            ]);

            $qrCode = trim($validated['qr_code']);
            $now = Carbon::now();

            // 1. Find student by QR type
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
                        'message' => 'Temporary QR code expired',
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
                        'message' => 'Permanent QR code inactive',
                        'data' => []
                    ], 403);
                }
            }

            // 2. Load student classes with fee/category details
            $classes = StudentStudentStudentClass::with([
                'studentClass',
                'studentClass.grade',
                'studentClass.subject',
                'categoryFee.classCategory',
            ])
                ->where('student_id', $student->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'student_student_student_classes_id' => $item->id,
                        'status' => (bool) $item->status,
                        'inactive_text' => $item->status ? 'active' : 'inactive',

                        'is_free_card' => (bool) $item->is_free_card,
                        'custom_fee' => $item->custom_fee,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_type' => $item->discount_type,
                        'default_fee' => $item->default_fee,
                        'final_fee' => $item->final_fee,
                        'fee_type' => $item->fee_type,

                        'student_class' => [
                            'id' => optional($item->studentClass)->id,
                            'class_name' => optional($item->studentClass)->class_name,
                            'medium' => optional($item->studentClass)->medium,
                        ],

                        'grade' => [
                            'id' => optional(optional($item->studentClass)->grade)->id,
                            'grade_name' => optional(optional($item->studentClass)->grade)->grade_name,
                        ],

                        'subject' => [
                            'id' => optional(optional($item->studentClass)->subject)->id,
                            'subject_name' => optional(optional($item->studentClass)->subject)->subject_name,
                        ],

                        'class_category_has_student_class' => [
                            'id' => optional($item->categoryFee)->id,
                            'fees' => optional($item->categoryFee)->fees,
                            'class_category' => [
                                'id' => optional(optional($item->categoryFee)->classCategory)->id,
                                'category_name' => optional(optional($item->categoryFee)->classCategory)->category_name,
                            ],
                        ],
                    ];
                })
                ->values();

            // 3. Student details
            $studentData = [
                'id' => $student->id,
                'custom_id' => $student->custom_id,
                'first_name' => $student->full_name,
                'last_name' => $student->initial_name,
                'guardian_mobile' => $student->guardian_mobile,
                'img_url' => $student->img_url,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Student data fetched successfully',
                'data' => [
                    'student' => $studentData,
                    'classes' => $classes,
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch student data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getStudentsByClassAndCategory($classId, $categoryId)
    {
        try {
            // Fetch all students for the given class and category
            $students = StudentStudentStudentClass::where('student_classes_id', $classId)
                ->where('class_category_has_student_class_id', $categoryId)
                ->get();

            // Check if no records found
            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'empty',
                    'message' => 'No students assigned to this class and category'
                ]);
            }

            // Return the records
            return response()->json([
                'status' => 'success',
                'data' => $students
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function allDetailsGetStudentsByClassAndCategory($classId, $categoryId)
    {
        try {
            $classId = (int) $classId;
            $categoryId = (int) $categoryId;

            $students = StudentStudentStudentClass::with([
                'student',
                'studentClass',
                'studentClass.grade',
                'studentClass.subject',
                'categoryFee.classCategory',
            ])
                ->where('student_classes_id', $classId)
                ->where('class_category_has_student_class_id', $categoryId)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'student_id' => $item->student_id,
                        'student_classes_id' => $item->student_classes_id,
                        'class_category_has_student_class_id' => $item->class_category_has_student_class_id,

                        'status' => (bool) $item->status,
                        'inactive_text' => $item->status ? 'active' : 'inactive',

                        'is_free_card' => (bool) $item->is_free_card,
                        'custom_fee' => $item->custom_fee,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_type' => $item->discount_type,
                        'default_fee' => $item->default_fee,
                        'final_fee' => $item->final_fee,
                        'fee_type' => $item->fee_type,

                        'student' => [
                            'id' => optional($item->student)->id,
                            'custom_id' => optional($item->student)->custom_id,
                            'full_name' => optional($item->student)->full_name,
                            'initial_name' => optional($item->student)->initial_name,
                            'guardian_mobile' => optional($item->student)->guardian_mobile,
                            'img_url' => optional($item->student)->img_url,
                        ],

                        'student_class' => [
                            'id' => optional($item->studentClass)->id,
                            'class_name' => optional($item->studentClass)->class_name,
                            'medium' => optional($item->studentClass)->medium,
                        ],

                        'grade' => [
                            'id' => optional(optional($item->studentClass)->grade)->id,
                            'grade_name' => optional(optional($item->studentClass)->grade)->grade_name,
                        ],

                        'subject' => [
                            'id' => optional(optional($item->studentClass)->subject)->id,
                            'subject_name' => optional(optional($item->studentClass)->subject)->subject_name,
                        ],

                        'class_category_has_student_class' => [
                            'id' => optional($item->categoryFee)->id,
                            'fees' => optional($item->categoryFee)->fees,
                            'class_category' => [
                                'id' => optional(optional($item->categoryFee)->classCategory)->id,
                                'category_name' => optional(optional($item->categoryFee)->classCategory)->category_name,
                            ],
                        ],
                    ];
                })
                ->values();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'empty',
                    'message' => 'No students assigned to this class and category.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'count' => $students->count(),
                'data' => $students
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch students.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentClassessDetails($student_id)
    {
        try {
            $student_id = (int) $student_id;

            $students = StudentStudentStudentClass::with([
                'student',
                'studentClass.teacher',
                'studentClass.subject',
                'studentClass.grade',
                'categoryFee.classCategory',
            ])
                ->where('student_id', $student_id)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'student_id' => $item->student_id,
                        'student_classes_id' => $item->student_classes_id,
                        'class_category_has_student_class_id' => $item->class_category_has_student_class_id,

                        'status' => (bool) $item->status,
                        'inactive_text' => $item->status ? 'active' : 'inactive',

                        'is_free_card' => (bool) $item->is_free_card,
                        'custom_fee' => $item->custom_fee,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_type' => $item->discount_type,
                        'default_fee' => $item->default_fee,
                        'final_fee' => $item->final_fee,
                        'fee_type' => $item->fee_type,

                        'student' => [
                            'id' => optional($item->student)->id,
                            'custom_id' => optional($item->student)->custom_id,
                            'full_name' => optional($item->student)->full_name,
                            'initial_name' => optional($item->student)->initial_name,
                            'guardian_mobile' => optional($item->student)->guardian_mobile,
                            'img_url' => optional($item->student)->img_url,
                        ],

                        'student_class' => [
                            'id' => optional($item->studentClass)->id,
                            'class_name' => optional($item->studentClass)->class_name,
                            'medium' => optional($item->studentClass)->medium,
                        ],

                        'teacher' => [
                            'id' => optional(optional($item->studentClass)->teacher)->id,
                            'teacher_name' => optional(optional($item->studentClass)->teacher)->teacher_name,
                        ],

                        'subject' => [
                            'id' => optional(optional($item->studentClass)->subject)->id,
                            'subject_name' => optional(optional($item->studentClass)->subject)->subject_name,
                        ],

                        'grade' => [
                            'id' => optional(optional($item->studentClass)->grade)->id,
                            'grade_name' => optional(optional($item->studentClass)->grade)->grade_name,
                        ],

                        'class_category_has_student_class' => [
                            'id' => optional($item->categoryFee)->id,
                            'fees' => optional($item->categoryFee)->fees,
                            'class_category' => [
                                'id' => optional(optional($item->categoryFee)->classCategory)->id,
                                'category_name' => optional(optional($item->categoryFee)->classCategory)->category_name,
                            ],
                        ],
                    ];
                })
                ->values();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'empty',
                    'message' => 'No classes found for this student',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'count' => $students->count(),
                'data' => $students
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch student classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentClassessFilterDetails($student_id)
    {
        try {
            $student_id = (int) $student_id;

            $students = StudentStudentStudentClass::with([
                'student',
                'studentClass.teacher',
                'studentClass.subject',
                'studentClass.grade',
                'categoryFee.classCategory',
            ])
                ->where('student_id', $student_id)
                ->get()
                ->map(function ($item) {
                    return [
                        'student_student_student_class_id' => $item->id,
                        'student_id' => $item->student_id,
                        'student_classes_id' => $item->student_classes_id,
                        'class_category_has_student_class_id' => $item->class_category_has_student_class_id,

                        'status' => (bool) $item->status,
                        'inactive_text' => $item->status ? 'active' : 'inactive',

                        'is_free_card' => (bool) $item->is_free_card,
                        'custom_fee' => $item->custom_fee,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_type' => $item->discount_type,
                        'default_fee' => $item->default_fee,
                        'final_fee' => $item->final_fee,
                        'fee_type' => $item->fee_type,

                        'joined_date' => optional($item->created_at)->toDateString(),

                        'class_category_has_student_class' => [
                            'id' => optional($item->categoryFee)->id,
                            'class_fee' => optional($item->categoryFee)->fees,
                        ],

                        'student' => [
                            'student_custom_id' => optional($item->student)->custom_id,
                            'first_name' => optional($item->student)->full_name,
                            'last_name' => optional($item->student)->initial_name,
                            'img_url' => optional($item->student)->img_url,
                            'guardian_mobile' => optional($item->student)->guardian_mobile,
                            'student_status' => optional($item->student)->is_active,
                        ],

                        'student_class' => [
                            'class_name' => optional($item->studentClass)->class_name,
                            'teacher' => [
                                'teacher_id' => optional(optional($item->studentClass)->teacher)->id,
                                'first_name' => optional(optional($item->studentClass)->teacher)->fname,
                                'last_name' => optional(optional($item->studentClass)->teacher)->lname,
                            ],
                            'subject' => [
                                'subject_name' => optional(optional($item->studentClass)->subject)->subject_name,
                            ],
                            'grade' => [
                                'grade_name' => optional(optional($item->studentClass)->grade)->grade_name,
                            ],
                        ],

                        'class_category' => [
                            'id' => optional(optional($item->categoryFee)->classCategory)->id,
                            'category_name' => optional(optional($item->categoryFee)->classCategory)->category_name,
                        ],
                    ];
                })
                ->values();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'empty',
                    'message' => 'No classes found for this student',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'count' => $students->count(),
                'data' => $students
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch student classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        try {
            DB::beginTransaction();

            // ✅ Validation
            $validated = $request->validate([
                'students' => 'required|array|min:1',
                'students.*.student_id' => 'required|integer|exists:students,id',
                'students.*.status' => 'nullable|boolean',
                'students.*.is_free_card' => 'nullable|boolean',
                'students.*.custom_fee' => 'nullable|numeric|min:0',
                'students.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
                'students.*.discount_type' => 'nullable|string|max:255',

                'student_classes_id' => 'required|integer|exists:student_classes,id',
                'class_category_has_student_class_id' => 'required|integer|exists:class_category_has_student_class,id',
            ]);

            $studentClassID = (int) $validated['student_classes_id'];
            $categoryID = (int) $validated['class_category_has_student_class_id'];

            // ✅ Ensure category belongs to class
            $categoryLink = ClassCategoryHasStudentClass::findOrFail($categoryID);

            if ((int)$categoryLink->student_classes_id !== $studentClassID) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Selected category does not belong to the selected class.'
                ], 422);
            }

            // ✅ Get existing records in one query (performance)
            $studentIds = collect($validated['students'])->pluck('student_id');

            $existing = StudentStudentStudentClass::where('student_classes_id', $studentClassID)
                ->where('class_category_has_student_class_id', $categoryID)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

            $created = [];
            $skipped = [];

            foreach ($validated['students'] as $studentData) {

                $studentId = (int) $studentData['student_id'];
                $status = array_key_exists('status', $studentData) ? (bool)$studentData['status'] : true;
                $isFreeCard = (bool)($studentData['is_free_card'] ?? false);
                $customFee = $studentData['custom_fee'] ?? null;
                $discountPercentage = $studentData['discount_percentage'] ?? null;
                $discountType = $studentData['discount_type'] ?? null;

                // ✅ Duplicate check (in-memory)
                if (isset($existing[$studentId])) {
                    $existingRecord = $existing[$studentId];

                    $skipped[] = [
                        'student_id' => $studentId,
                        'message' => $existingRecord->status
                            ? 'duplicate entry'
                            : 'duplicate entry — class inactive'
                    ];
                    continue;
                }

                // ✅ Business rules

                // Free card override
                if ($isFreeCard) {
                    $customFee = null;
                    $discountPercentage = null;
                    $discountType = $discountType ?: 'free_card';
                }

                // Cannot have both
                if (!$isFreeCard && !is_null($customFee) && !is_null($discountPercentage)) {
                    $skipped[] = [
                        'student_id' => $studentId,
                        'message' => 'custom_fee and discount_percentage cannot both be set'
                    ];
                    continue;
                }

                // Auto detect half card
                if (!$isFreeCard && is_null($customFee) && !is_null($discountPercentage)) {
                    if ((float)$discountPercentage === 50.0 && is_null($discountType)) {
                        $discountType = 'half_card';
                    }
                }

                try {
                    $record = StudentStudentStudentClass::create([
                        'student_id' => $studentId,
                        'student_classes_id' => $studentClassID,
                        'class_category_has_student_class_id' => $categoryID,
                        'status' => $status,
                        'is_free_card' => $isFreeCard,
                        'custom_fee' => $customFee,
                        'discount_percentage' => $discountPercentage,
                        'discount_type' => $discountType,
                    ]);

                    $created[] = [
                        'id' => $record->id,
                        'student_id' => $record->student_id,
                        'status' => $record->status,
                        'is_free_card' => $record->is_free_card,
                        'final_fee' => $record->final_fee,
                        'fee_type' => $record->fee_type,
                    ];
                } catch (\Illuminate\Database\QueryException $e) {

                    // ✅ Handle race condition (duplicate from DB unique key)
                    if ($e->getCode() === '23000') {
                        $skipped[] = [
                            'student_id' => $studentId,
                            'message' => 'duplicate (DB constraint)'
                        ];
                        continue;
                    }

                    throw $e;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk records processed',
                'created_count' => count($created),
                'skipped_count' => count($skipped),
                'created' => $created,
                'skipped' => $skipped,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process bulk records',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeSingleStudentClass(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validation
            $validated = $request->validate([
                'student_id' => 'required|integer|exists:students,id',
                'student_classes_id' => 'required|integer|exists:student_classes,id',
                'class_category_has_student_class_id' => 'required|integer|exists:class_category_has_student_class,id',
                'status' => 'nullable|boolean',
                'is_free_card' => 'nullable|boolean',
                'custom_fee' => 'nullable|numeric|min:0',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'discount_type' => 'nullable|string|max:255',
            ]);

            $studentId = (int) $validated['student_id'];
            $studentClassID = (int) $validated['student_classes_id'];
            $categoryID = (int) $validated['class_category_has_student_class_id'];

            $status = array_key_exists('status', $validated) ? (bool) $validated['status'] : true;
            $isFreeCard = (bool) ($validated['is_free_card'] ?? false);
            $customFee = $validated['custom_fee'] ?? null;
            $discountPercentage = $validated['discount_percentage'] ?? null;
            $discountType = $validated['discount_type'] ?? null;

            // Ensure category belongs to selected class
            $categoryLink = ClassCategoryHasStudentClass::findOrFail($categoryID);

            if ((int) $categoryLink->student_classes_id !== $studentClassID) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Selected category does not belong to the selected class.'
                ], 422);
            }

            // Business rules
            if ($isFreeCard) {
                $customFee = null;
                $discountPercentage = null;
                $discountType = $discountType ?: 'free_card';
            }

            if (!$isFreeCard && !is_null($customFee) && !is_null($discountPercentage)) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'custom_fee and discount_percentage cannot both be set.'
                ], 422);
            }

            if (
                !$isFreeCard &&
                is_null($customFee) &&
                !is_null($discountPercentage) &&
                is_null($discountType) &&
                (float) $discountPercentage === 50.0
            ) {
                $discountType = 'half_card';
            }

            // Check duplicate
            $existingRecord = StudentStudentStudentClass::where([
                'student_id' => $studentId,
                'student_classes_id' => $studentClassID,
                'class_category_has_student_class_id' => $categoryID,
            ])->first();

            if ($existingRecord) {
                DB::rollBack();

                $message = $existingRecord->status
                    ? 'duplicate entry'
                    : 'duplicate entry — class inactive';

                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 409);
            }

            try {
                $record = StudentStudentStudentClass::create([
                    'student_id' => $studentId,
                    'student_classes_id' => $studentClassID,
                    'class_category_has_student_class_id' => $categoryID,
                    'status' => $status,
                    'is_free_card' => $isFreeCard,
                    'custom_fee' => $customFee,
                    'discount_percentage' => $discountPercentage,
                    'discount_type' => $discountType,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // DB unique constraint race condition
                if ($e->getCode() === '23000') {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'duplicate entry (DB constraint)'
                    ], 409);
                }

                throw $e;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Record created successfully',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Activate a student class record
    public function activateStudentClass($id)
    {
        try {
            $record = StudentStudentStudentClass::findOrFail($id);

            $record->status = 1; // set active
            $record->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Student class record activated',
                'record' => $record
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Deactivate a student class record
    public function deactivateStudentClass($id)
    {
        try {
            $record = StudentStudentStudentClass::findOrFail($id);

            $record->status = 0; // set inactive
            $record->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Student class record deactivated',
                'record' => $record
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDeactivateStudentClasses(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'student_class_ids' => 'required|array|min:1',
                'student_class_ids.*' => 'required|integer|exists:student_student_student_classes,id',
            ]);

            $deactivated = [];
            $skipped = [];

            foreach ($validated['student_class_ids'] as $id) {
                $record = StudentStudentStudentClass::find($id);

                if (!$record) {
                    $skipped[] = [
                        'id' => $id,
                        'message' => 'Record not found'
                    ];
                    continue;
                }

                if ($record->status == 0) {
                    $skipped[] = [
                        'id' => $id,
                        'message' => 'Already inactive'
                    ];
                    continue;
                }

                $record->status = 0;
                $record->save();
                $record->inactive_text = "inactive";

                $deactivated[] = $record;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk deactivation processed',
                'deactivated_count' => count($deactivated),
                'skipped' => $skipped,
                'deactivated_records' => $deactivated
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleStudentClassStatus($id)
    {
        try {
            $record = StudentStudentStudentClass::findOrFail($id);

            // Toggle status
            $record->status = $record->status == 1 ? 0 : 1;
            $record->save();

            $message = $record->status == 1
                ? 'Student class record activated'
                : 'Student class record deactivated';

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'record' => $record
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update record',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
