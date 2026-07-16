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

        $qrCode = trim($request->qr_code);
        $now = Carbon::now();

        $student = Student::where('student_disable', false)
            ->where(function ($query) use ($qrCode) {
                $query->where('temporary_qr_code', $qrCode)
                    ->orWhere('custom_id', $qrCode);
            })
            ->first();

        if (!$student) {
            throw new Exception('QR code invalid');
        }

        // TMP QR code
        if ($student->temporary_qr_code === $qrCode) {

            // if (
            //     $student->temporary_qr_code_expire_date &&
            //     $now->gt($student->temporary_qr_code_expire_date)
            // ) {
            //     throw new Exception('Temporary QR code has expired');
            // }

            return $student->id;
        }

        // SA/custom_id
        if ($student->custom_id === $qrCode) {

            if (!$student->permanent_qr_active) {
                $student->update([
                    'permanent_qr_active' => true,
                ]);
            }

            return $student->id;
        }

        throw new Exception('QR code invalid');
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
