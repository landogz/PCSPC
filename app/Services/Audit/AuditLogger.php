<?php

namespace App\Services\Audit;

use App\Models\AuthActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(string $event, array $meta = [], ?User $actor = null, ?Request $request = null): void
    {
        $request ??= request();
        $actor ??= Auth::user();

        AuthActivityLog::query()->create([
            'user_id' => $actor?->id,
            'email' => $actor?->email,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
