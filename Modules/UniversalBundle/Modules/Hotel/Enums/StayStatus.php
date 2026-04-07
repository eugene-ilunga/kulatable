<?php

namespace Modules\Hotel\Enums;

enum StayStatus: string
{
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case EXTENDED = 'extended';

    public function label(): string
    {
        return match($this) {
            self::CHECKED_IN => 'Checked In',
            self::CHECKED_OUT => 'Checked Out',
            self::EXTENDED => 'Extended',
        };
    }
}
