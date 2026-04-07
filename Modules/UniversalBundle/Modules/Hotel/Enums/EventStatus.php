<?php

namespace Modules\Hotel\Enums;

enum EventStatus: string
{
    case CONFIRMED = 'confirmed';
    case TENTATIVE = 'tentative';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::CONFIRMED => 'Confirmed',
            self::TENTATIVE => 'Tentative',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }
}
