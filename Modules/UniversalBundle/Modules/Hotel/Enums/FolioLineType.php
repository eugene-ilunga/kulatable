<?php

namespace Modules\Hotel\Enums;

enum FolioLineType: string
{
    case ROOM_CHARGE = 'room_charge';
    case FNB_POSTING = 'fnb_posting';
    case MINIBAR = 'minibar';
    case LAUNDRY = 'laundry';
    case SPA = 'spa';
    case TRANSPORT = 'transport';
    case DISCOUNT = 'discount';
    case TAX = 'tax';
    case ADJUSTMENT = 'adjustment';
    case DAMAGE = 'damage';
    case ADVANCE = 'advance';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::ROOM_CHARGE => 'Room Charge',
            self::FNB_POSTING => 'Food & Beverage',
            self::MINIBAR => 'Minibar',
            self::LAUNDRY => 'Laundry',
            self::SPA => 'Spa',
            self::TRANSPORT => 'Transport',
            self::DISCOUNT => 'Discount',
            self::TAX => 'Tax',
            self::ADJUSTMENT => 'Adjustment',
            self::DAMAGE => 'Damage',
            self::ADVANCE => 'Advance Payment',
            self::OTHER => 'Other',
        };
    }
}
