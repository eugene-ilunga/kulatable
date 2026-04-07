<?php

namespace Modules\Hotel\Enums;

enum FolioStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case TRANSFERRED = 'transferred';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::TRANSFERRED => 'Transferred',
        };
    }
}
