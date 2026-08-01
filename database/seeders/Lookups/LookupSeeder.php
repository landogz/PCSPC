<?php

namespace Database\Seeders\Lookups;

use App\Models\LookupValue;
use App\Services\Lookups\LookupService;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        /** @var LookupService $service */
        $service = app(LookupService::class);

        foreach (array_keys(config('lookups.types', [])) as $type) {
            $sort = 10;
            foreach ($service->defaultLabels($type) as $code => $label) {
                LookupValue::query()->updateOrCreate(
                    ['type' => $type, 'code' => $code],
                    [
                        'label' => $label,
                        'sort_order' => $sort,
                        'is_active' => true,
                        'is_system' => true,
                        'description' => null,
                    ],
                );
                $sort += 10;
            }
        }
    }
}
