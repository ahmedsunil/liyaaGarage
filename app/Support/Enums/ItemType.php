<?php

namespace App\Support\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ItemType: string implements HasColor, HasIcon, HasLabel
{
    case PRODUCT = '0';
    case SERVICE = '1';


    public function getLabel(): string
    {
        return match ($this) {
            self::PRODUCT => 'Product',
            self::SERVICE => 'Service',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PRODUCT => 'success',
            self::SERVICE => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PRODUCT => 'heroicon-m-shopping-bag',
            self::SERVICE => 'heroicon-m-wrench-screwdriver',
        };
    }
}
