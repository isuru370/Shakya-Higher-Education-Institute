<?php

namespace App\Services;

use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class ReadQRCodeService
{
    public function readQRCode(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        try {
            $qrCode = trim($request->qr_code);
            $now = Carbon::now();

            // TMP හෝ SA/custom_id දෙකම search කරනවා
            $student = Student::where('student_disable', false)
                ->where(function ($query) use ($qrCode) {
                    $query->where('temporary_qr_code', $qrCode)
                        ->orWhere('custom_id', $qrCode);
                })
                ->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR code invalid'
                ], 404);
            }

            // TMP QR නම් expired date එක විතරක් check කරනවා
            if ($student->temporary_qr_code === $qrCode) {
                if ($student->temporary_qr_code_expire_date && $now->gt($student->temporary_qr_code_expire_date)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Temporary QR code has expired'
                    ], 403);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Temporary QR code valid',
                    'student_id' => $student->id
                ], 200);
            }

            // SA/custom_id නම් permanent_qr_active check නොකර valid කරනවා
            return response()->json([
                'status' => 'success',
                'message' => 'Original QR code valid',
                'student_id' => $student->id
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function studentIdCardActive($custom_id)
    {
        try {
            // Find the student by custom_id
            $student = Student::where('custom_id', $custom_id)->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found',
                ], 404);
            }

            // Update the flags
            $student->update([
                'permanent_qr_active' => true,
                'student_disable' => false,
                'is_active' => true,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Student QR activated successfully',
                'student_id' => $student->id,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate student QR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
