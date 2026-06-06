<?php

namespace App\Imports;

use App\Models\QuickPhoto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;

class QuickPhotosImport implements ToCollection
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
                    QuickPhoto::where(
                        'custom_id',
                        $data['custom_id']
                    )->exists()
                ) {
                    DB::rollBack();
                    continue;
                }

                QuickPhoto::create([
                    'custom_id' => $data['custom_id'],
                    'image_path' => $data['quick_img'],
                    'is_active' => $data['is_active'] ?? 1,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                ]);

                DB::commit();
            } catch (\Exception $e) {

                DB::rollBack();

                logger()->error(
                    'Quick Photo Import Error',
                    [
                        'custom_id' => $data['custom_id'] ?? null,
                        'message' => $e->getMessage()
                    ]
                );
            }
        }
    }
}
