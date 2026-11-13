<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ServiceType: int implements HasColor, HasLabel
{
    case Gas = 1;
    case Water = 2;
    case Electricity = 3;
    case HotWater = 4;
    case Heating = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::Gas => __('Gas'),
            self::Water => __('Water'),
            self::Electricity => __('Electricity'),
            self::HotWater => __('Hot water'),
            self::Heating => __('Heating'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Gas => 'warning',
            self::Water => 'info',
            self::Electricity => 'success',
            self::HotWater, self::Heating => 'danger',
        };
    }

    public function getUnit(): string
    {
        return match ($this) {
            self::Gas, self::Water, self::HotWater => __('m³'),
            self::Electricity => __('kWh'),
            self::Heating => __('Gcal'),
        };
    }
}
