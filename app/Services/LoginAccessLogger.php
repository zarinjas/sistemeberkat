<?php

namespace App\Services;

use App\Models\LoginAccessLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginAccessLogger
{
    public function __construct(
        private readonly IpGeolocationService $geolocationService,
    ) {
    }

    public function log(Request $request, User $user, string $loginType = 'standard'): void
    {
        try {
            $ipAddress = $request->ip();
            $location = $this->geolocationService->locate($ipAddress);

            LoginAccessLog::query()->create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'login_type' => $loginType,
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'country' => $location['country'],
                'region' => $location['region'],
                'city' => $location['city'],
                'isp' => $location['isp'],
                'location_summary' => $location['location_summary'],
                'logged_in_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Never block successful authentication if audit logging fails.
            Log::warning('Failed to write login access log.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'login_type' => $loginType,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
