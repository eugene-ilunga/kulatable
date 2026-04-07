<?php

namespace Modules\Hotel\Enums;

enum HousekeepingTaskType: string
{
    case CLEAN = 'clean';
    case INSPECT = 'inspect';
    case DEEP_CLEAN = 'deep_clean';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match($this) {
            self::CLEAN => 'Clean',
            self::INSPECT => 'Inspect',
            self::DEEP_CLEAN => 'Deep Clean',
            self::MAINTENANCE => 'Maintenance',
        };
    }
}
