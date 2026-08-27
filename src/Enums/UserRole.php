<?php

namespace Kreetancraft\UserManagement\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case PackageManager = 'package-manager';
    case BookingManager = 'booking-manager';
    case FinanceAdmin = 'finance-admin';

    /**
     * Human-readable label for UI display.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::PackageManager => 'Package Manager',
            self::BookingManager => 'Booking Manager',
            self::FinanceAdmin => 'Finance Admin',
        };
    }

    /**
     * Short description of the role's responsibilities.
     */
    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Full access to all modules and settings.',
            self::PackageManager => 'Manages trips, departures, pricing, and capacity.',
            self::BookingManager => 'Manages bookings, inquiries, quotes, and customers.',
            self::FinanceAdmin => 'Manages payments, invoices, refunds, and financial reports.',
        };
    }

    /**
     * Badge color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'red',
            self::PackageManager => 'blue',
            self::BookingManager => 'green',
            self::FinanceAdmin => 'amber',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->toArray();
    }
}
