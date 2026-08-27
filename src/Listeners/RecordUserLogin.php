<?php

namespace Kreetancraft\UserManagement\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Kreetancraft\UserManagement\Models\User;

class RecordUserLogin
{
    /**
     * Stamp last_login_at + last_login_ip and persist a login-history row.
     *
     * Geo-IP enrichment lives here (not on the User model) so the model stays
     * a thin persistence layer. Silently ignores non-User authentications.
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $ip = Request::ip();
        $userAgent = Request::userAgent();

        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        $location = $this->resolveLocation($ip);

        $event->user->loginHistories()->create([
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'city' => $location['city'] ?? null,
            'state' => $location['state'] ?? null,
            'country' => $location['country'] ?? null,
            'country_code' => $location['country_code'] ?? null,
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function resolveLocation(?string $ip): array
    {
        if ($ip === null) {
            return [];
        }

        if (! function_exists('geoip') || ! app()->bound('geoip')) {
            return [];
        }

        try {
            $geo = geoip($ip);

            return Cache::remember("geoip.{$ip}", now()->addDays(7), fn () => [
                'city' => $geo->city,
                'state' => $geo->state,
                'country' => $geo->country,
                'country_code' => $geo->iso_code,
            ]);
        } catch (\Throwable) {
            return [];
        }
    }
}
