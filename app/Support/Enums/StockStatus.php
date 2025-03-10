<?php

namespace App\Support\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockStatus: string implements HasColor, HasIcon, HasLabel
{
    case IN_STOCK = 'in_stock';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case AVAILABLE = 'available';


    public function getLabel(): string
    {
        return match ($this) {
            self::IN_STOCK => 'In Stock',
            self::LOW_STOCK => 'Low Stock',
            self::OUT_OF_STOCK => 'Out of Stock',
            self::AVAILABLE => 'Available',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::IN_STOCK => 'success',
            self::LOW_STOCK => 'danger',
            self::OUT_OF_STOCK => 'danger',
            self::AVAILABLE => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::IN_STOCK => 'heroicon-m-shopping-cart',
            self::LOW_STOCK => 'heroicon-m-arrow-trending-down',
            self::OUT_OF_STOCK => 'heroicon-m-information-circle',
            self::AVAILABLE => 'heroicon-m-wrench',
        };
    }
}
