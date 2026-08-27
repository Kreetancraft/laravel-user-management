<?php

namespace Kreetancraft\UserManagement\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserLoginHistory extends Model
{
    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'city',
        'state',
        'country',
        'country_code',
    ];

    protected $hidden = [
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function browser(): Attribute
    {
        return Attribute::get(function (): string {
            $ua = $this->user_agent ?? '';
            if (str_contains($ua, 'Firefox')) {
                return 'Firefox';
            }
            if (str_contains($ua, 'Chrome') && ! str_contains($ua, 'Edg')) {
                return 'Chrome';
            }
            if (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')) {
                return 'Safari';
            }
            if (str_contains($ua, 'Edg')) {
                return 'Edge';
            }
            if (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) {
                return 'Opera';
            }

            return 'Unknown';
        });
    }

    protected function platform(): Attribute
    {
        return Attribute::get(function (): string {
            $ua = $this->user_agent ?? '';
            if (str_contains($ua, 'Windows')) {
                return 'Windows';
            }
            if (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) {
                return 'macOS';
            }
            if (str_contains($ua, 'Linux')) {
                return 'Linux';
            }
            if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
                return 'iOS';
            }
            if (str_contains($ua, 'Android')) {
                return 'Android';
            }

            return 'Unknown';
        });
    }

    protected function countryFlag(): Attribute
    {
        return Attribute::get(function (): string {
            $code = Str::upper($this->country_code ?? '');
            if (strlen($code) !== 2) {
                return '📍';
            }

            $chr1 = ord($code[0]) - 65 + 127462;
            $chr2 = ord($code[1]) - 65 + 127462;

            return mb_chr($chr1, 'UTF-8').mb_chr($chr2, 'UTF-8');
        });
    }

    protected function formattedLocation(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = array_filter([$this->city, $this->state, $this->country]);

            return count($parts) > 0 ? implode(', ', $parts) : __('Unknown Location');
        });
    }
}
