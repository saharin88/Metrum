<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ServiceType: int implements HasColor, HasLabel
{
    case Gas = 1;
    case Water = 2;
    case Electricity = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::Gas => __('Gas'),
            self::Water => __('Water'),
            self::Electricity => __('Electricity'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Gas => 'warning',
            self::Water => 'info',
            self::Electricity => 'success',
        };
    }
}
