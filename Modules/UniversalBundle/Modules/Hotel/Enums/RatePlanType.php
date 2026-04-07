<?php

namespace Modules\Hotel\Enums;

enum RatePlanType: string
{
    case EP = 'ep'; // European Plan (Room only)
    case CP = 'cp'; // Continental Plan (Room + Breakfast)
    case MAP = 'map'; // Modified American Plan (Room + Breakfast + Dinner)
    case AP = 'ap'; // American Plan (Room + All Meals)

    public function label(): string
    {
        return match($this) {
            self::EP => 'EP (Room Only)',
            self::CP => 'CP (Room + Breakfast)',
            self::MAP => 'MAP (Room + Breakfast + Dinner)',
            self::AP => 'AP (Room + All Meals)',
        };
    }
}
