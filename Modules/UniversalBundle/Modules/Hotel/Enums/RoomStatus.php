<?php

namespace Modules\Hotel\Enums;

enum RoomStatus: string
{
    case VACANT_CLEAN = 'vacant_clean';
    case VACANT_DIRTY = 'vacant_dirty';
    case OCCUPIED = 'occupied';
    case OUT_OF_SERVICE = 'out_of_service';
    case OUT_OF_ORDER = 'out_of_order';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match($this) {
            self::VACANT_CLEAN => 'Vacant Clean',
            self::VACANT_DIRTY => 'Vacant Dirty',
            self::OCCUPIED => 'Occupied',
            self::OUT_OF_SERVICE => 'Out of Service',
            self::OUT_OF_ORDER => 'Out of Order',
            self::MAINTENANCE => 'Maintenance',
        };
    }
}
