<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentIdCard;
use App\Models\StudentPortalLogin;
use App\Models\TemporaryIdCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class StudentsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $header = $rows->shift();

        foreach ($rows as $row) {

            DB::beginTransaction();

            try {

                $data = array_combine(
                    $header->toArray(),
                    $row->toArray()
                );

                // Skip duplicate custom id
                if (
                    Student::where(
                        'custom_id',
                        $data['custom_id']
                    )->exists()
                ) {
                    DB::rollBack();
                    continue;
                }

                $student = Student::create([

                    'custom_id'                     => $data['custom_id'],
                    'temporary_qr_code'            => $data['temporary_qr_code'] ?: null,
                    'temporary_qr_code_expire_date' => $data['temporary_qr_code_expire_date'] ?: null,

                    'full_name'                    => $data['full_name'] ?: null,
                    'initial_name'                 => $data['initial_name'],

                    'mobile'                       => $data['mobile'] ?: null,
                    'whatsapp_mobile'              => $data['whatsapp_mobile'] ?: null,
                    'email'                        => $data['email'] ?: null,

                    'nic' => (!empty($data['nic']) && $data['nic'] !== 'NULL')
                        ? trim($data['nic'])
                        : null,
                    'bday'                         => $data['bday'] ?: null,

                    'gender'                       => $data['gender'] ?: 'other',

                    'address1'                     => $data['address1'] ?: null,
                    'address2'                     => $data['address2'] ?: null,
                    'address3'                     => $data['address3'] ?: null,

                    'guardian_fname'               => $data['guardian_fname'] ?: null,
                    'guardian_lname'               => $data['guardian_lname'] ?: null,
                    'guardian_nic' => (!empty($data['guardian_nic']) && $data['guardian_nic'] !== 'NULL')
                        ? trim($data['guardian_nic'])
                        : null,
                    'guardian_mobile'              => $data['guardian_mobile'],

                    'grade_id'                     => $data['grade_id'],

                    'class_type'                   => ucfirst(
                        strtolower(
                            $data['class_type'] ?? 'Offline'
                        )
                    ),

                    'admission'                    => $data['admission'] ?? 0,

                    'student_school'               => $data['student_school'] ?: null,

                    'img_url'                      => $data['img_url']
                        ?: 'uploads/male.png',

                    'is_active'                    => $data['is_active'] ?? 1,
                    'permanent_qr_active'          => $data['permanent_qr_active'] ?? 0,
                    'student_disable'             => $data['student_disable'] ?? 0,

                    'created_at'                   => $data['created_at'],
                    'updated_at'                   => $data['updated_at'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Temporary Card Link
                |--------------------------------------------------------------------------
                */

                if (!empty($student->temporary_qr_code)) {

                    $tempCard = TemporaryIdCard::where(
                        'temporary_id_number',
                        $student->temporary_qr_code
                    )->first();

                    if ($tempCard) {

                        $tempCard->update([
                            'student_id' => $student->id,
                            'status' => 'active',
                            'activated_at' => now(),
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Student ID Card
                |--------------------------------------------------------------------------
                */

                StudentIdCard::create([
                    'student_id' => $student->id,
                    'status' => 'active',
                    'registration_status' => 'completed',
                    'student_fee' => 350,
                    'print_cost' => 90,
                    'is_reissue' => false,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Portal Login
                |--------------------------------------------------------------------------
                */

                StudentPortalLogin::create([
                    'student_id' => $student->id,
                    'username' => $student->custom_id,
                    'password' => Hash::make('12345678'),
                    'is_verified' => true,
                    'is_active' => true,
                ]);

                DB::commit();
            } catch (\Exception $e) {

                DB::rollBack();

                logger()->error(
                    'Student Import Error',
                    [
                        'custom_id' => $data['custom_id'] ?? null,
                        'message' => $e->getMessage()
                    ]
                );
            }
        }
    }
}
