<?php

namespace App\Actions\Hero;

use App\Models\HeroSlide;
use Illuminate\Support\Facades\DB;

class ReorderHeroSlidesAction
{
    /**
     * @param  list<string>  $orderedIds
     */
    public function execute(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $slideId) {
                HeroSlide::query()
                    ->where('id', $slideId)
                    ->update(['sort_order' => $index]);
            }
        });
    }
}
