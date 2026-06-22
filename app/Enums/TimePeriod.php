<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum TimePeriod: string
{
    case ONE_HOUR = '1 hour';
    case SIX_HOURS = '6 hours';
    case ONE_DAY = '1 day';
    case FOURTEEN_DAYS = '14 days';
    case THIRTY_DAYS = '30 days';

    /**
     * Get all values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Convert the enum case into a Carbon datetime instance.
     */
    public function toCarbon(): CarbonInterface
    {
        return match ($this) {
            self::ONE_HOUR => now()->addHour(),
            self::SIX_HOURS => now()->addHours(6),
            self::ONE_DAY => now()->addDay(),
            self::FOURTEEN_DAYS => now()->addDays(14),
            self::THIRTY_DAYS => now()->addDays(30),
        };
    }
}
