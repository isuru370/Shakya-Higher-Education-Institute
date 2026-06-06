<?php

namespace App\Imports;

use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class TeachersImport implements ToCollection
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

                if (
                    Teacher::where(
                        'custom_id',
                        $data['custom_id']
                    )->exists()
                ) {
                    DB::rollBack();
                    continue;
                }

                Teacher::create([

                    'custom_id' => $data['custom_id'],

                    'full_name' =>
                    trim($data['fname'] . ' ' . $data['lname']),

                    'initials' =>
                    trim($data['fname']),

                    'email' =>
                    !empty($data['email'])
                        ? trim($data['email'])
                        : null,

                    'mobile' =>
                    !empty($data['mobile'])
                        ? trim($data['mobile'])
                        : null,

                    'nic' =>
                    !empty($data['nic'])
                        ? trim($data['nic'])
                        : null,

                    'bday' => $data['bday'],

                    'gender' =>
                    strtolower($data['gender']) ?: 'other',

                    'address1' => $data['address1'],

                    'address2' =>
                    $data['address2'] ?: null,

                    'address3' =>
                    $data['address3'] ?: null,

                    'is_active' =>
                    $data['is_active'] ?? 1,

                    'graduation_details' =>
                    $data['graduation_details'] ?: null,

                    'experience' =>
                    $data['experience'] ?: null,

                    'account_number' =>
                    $data['account_number'] ?: null,

                    'bank_branch_id' => (!empty($data['bank_branch_id']) &&
                        strtoupper(trim($data['bank_branch_id'])) !== 'NULL')
                        ? (int) $data['bank_branch_id']
                        : null,

                    'created_at' =>
                    $data['created_at'],

                    'updated_at' =>
                    $data['updated_at'],
                ]);

                DB::commit();
            } catch (\Exception $e) {

                DB::rollBack();

                logger()->error(
                    'Teacher Import Error',
                    [
                        'custom_id' =>
                        $data['custom_id'] ?? null,
                        'message' =>
                        $e->getMessage()
                    ]
                );
            }
        }
    }
}
