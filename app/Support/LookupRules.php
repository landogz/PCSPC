<?php

namespace App\Support;

use App\Services\Lookups\LookupService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

final class LookupRules
{
    /**
     * @param  list<string>|null  $fallback
     */
    public static function in(string $type, ?array $fallback = null): In
    {
        return Rule::in(app(LookupService::class)->activeCodes($type, $fallback));
    }
}
