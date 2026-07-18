<?php

namespace App\Actions\Doctor;

use App\Models\DoctorProfile;
use Illuminate\Support\Facades\DB;

class ReorderDoctorsAction
{
    /**
     * @param  list<string>  $orderedIds
     */
    public function execute(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $doctorId) {
                DoctorProfile::query()
                    ->where('user_id', $doctorId)
                    ->update(['sort_order' => $index]);
            }
        });
    }
}
