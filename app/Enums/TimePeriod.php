<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

enum TimePeriod: string
{
    case ONE_HOUR = '1 hour';
    case SIX_HOURS = '6 hours';
    case ONE_DAY = '1 day';
    case FOURTEEN_DAYS = '14 days';
    case THIRTY_DAYS = '30 days';

    /**
     * Get all values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Convert the enum case into a Carbon datetime instance.
     */
    public function toCarbon(): CarbonImmutable
    {
        return match ($this) {
            self::ONE_HOUR => CarbonImmutable::now()->addHour(),
            self::SIX_HOURS => CarbonImmutable::now()->addHours(6),
            self::ONE_DAY => CarbonImmutable::now()->addDay(),
            self::FOURTEEN_DAYS => CarbonImmutable::now()->addDays(14),
            self::THIRTY_DAYS => CarbonImmutable::now()->addDays(30),
        };
    }
}
