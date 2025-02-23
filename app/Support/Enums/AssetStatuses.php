<?php

namespace App\Support\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssetStatuses: string implements HasColor, HasIcon, HasLabel
{
    case USABLE = 'usable';
    case DAMAGED = 'damaged';
    case MAINTENANCE = 'maintenance';

    /**
     * Get the human-readable label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::USABLE => 'Usable',
            self::DAMAGED => 'Damaged',
            self::MAINTENANCE => 'Under Maintenance',
        };
    }

    /**
     * Get the color associated with the status.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::USABLE => 'success',
            self::DAMAGED => 'danger',
            self::MAINTENANCE => 'warning',
        };
    }

    /**
     * Get the icon associated with the status.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::USABLE => 'heroicon-o-check-circle', // Green checkmark
            self::DAMAGED => 'heroicon-o-x-circle',    // Red X
            self::MAINTENANCE => 'heroicon-o-wrench',  // Yellow wrench
        };
    }
}
