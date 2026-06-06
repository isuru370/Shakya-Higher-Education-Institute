<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use App\Models\Payment;
use App\Models\StudentClassEnrollment;

use Maatwebsite\Excel\Concerns\ToCollection;

class PaymentsImport implements ToCollection
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

                $enrollment = StudentClassEnrollment::find(
                    $data['student_student_student_classes_id']
                );

                if (!$enrollment) {

                    logger()->info('Enrollment Missing', [
                        'old_enrollment_id' =>
                        $data['student_student_student_classes_id'],

                        'student_id' =>
                        $data['student_id'],
                    ]);

                    DB::rollBack();
                    continue;
                }

                $paidAt = Carbon::parse(
                    $data['payment_date']
                );

                /*
                |--------------------------------------------------------------------------
                | Payment Month
                |--------------------------------------------------------------------------
                */

                $paymentMonth = null;

                if (!empty($data['payment_for'])) {

                    try {

                        // Example: 2026 Feb
                        $paymentMonth = Carbon::createFromFormat(
                            'Y M',
                            trim($data['payment_for'])
                        )->startOfMonth();
                    } catch (\Exception $e) {

                        $paymentMonth =
                            $paidAt->copy()->startOfMonth();
                    }
                } else {

                    $paymentMonth =
                        $paidAt->copy()->startOfMonth();
                }

                /*
|--------------------------------------------------------------------------
| Skip unpaid / inactive payments
|--------------------------------------------------------------------------
*/

                if ((int)($data['status'] ?? 0) === 0) {

                    logger()->info('Payment Skipped - Status 0', [
                        'student_id' => $data['student_id'],
                        'enrollment_id' => $enrollment->id,
                        'payment_for' => $data['payment_for'] ?? null,
                    ]);

                    DB::rollBack();
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate check
                |--------------------------------------------------------------------------
                */

                $exists = Payment::where(
                    'student_class_enrollment_id',
                    $enrollment->id
                )
                    ->whereDate(
                        'payment_month',
                        $paymentMonth->format('Y-m-d')
                    )
                    ->exists();

                if ($exists) {

                    logger()->info('Duplicate Payment', [
                        'enrollment_id' =>
                        $enrollment->id,

                        'payment_month' =>
                        $paymentMonth->format('Y-m-d'),

                        'student_id' =>
                        $data['student_id'],
                    ]);

                    DB::rollBack();
                    continue;
                }

                Payment::create([

                    'local_uuid' =>
                    (string) Str::uuid(),

                    'student_id' =>
                    $data['student_id'],

                    'student_class_enrollment_id' =>
                    $enrollment->id,

                    'user_id' =>
                    $data['user_id'],

                    'mark_method' =>
                    'manual_web',

                    'amount' =>
                    $data['amount'],

                    'discount_amount' =>
                    0,

                    'paid_at' =>
                    $paidAt,

                    'payment_month' =>
                    $paymentMonth->format('Y-m-d'),

                    'payment_method' =>
                    'cash',

                    'status' => ((int)$data['status'] === 1)
                        ? 'completed'
                        : 'pending',

                    'receipt_number' =>
                    null,

                    'reference_number' =>
                    null,

                    'is_synced' =>
                    true,

                    'note' =>
                    $data['payment_for'] ?? null,

                    'created_at' =>
                    $data['created_at'],

                    'updated_at' =>
                    $data['updated_at'],
                ]);

                DB::commit();
            } catch (\Exception $e) {

                DB::rollBack();

                logger()->error(
                    'Payment Import Error',
                    [
                        'student_id' =>
                        $data['student_id'] ?? null,

                        'enrollment_id' =>
                        $data['student_student_student_classes_id'] ?? null,

                        'message' =>
                        $e->getMessage()
                    ]
                );
            }
        }
    }
}
