<?php

namespace App\Enums;

enum CustomerType: int
{
    case INDIVIDUAL = 1;
    case BUSINESS = 2;

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Cá nhân',
            self::BUSINESS => 'Cộng tác viên',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'bg-blue-100 text-blue-800',
            self::BUSINESS => 'bg-green-100 text-green-800',
        };
    }

    public static function options(): array
    {
        return [
            ['value' => self::INDIVIDUAL->value, 'label' => self::INDIVIDUAL->label()],
            ['value' => self::BUSINESS->value, 'label' => self::BUSINESS->label()],
        ];
    }
}
