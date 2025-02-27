<?php

namespace App\Support\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasIcon, HasLabel
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case COVERED = 'covered';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAID => 'Paid',
            self::PENDING => 'Pending',
            self::COVERED => 'Covered',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PAID => 'success',
            self::PENDING => 'danger',
            self::COVERED => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PAID => 'heroicon-m-check-circle',
            self::PENDING => 'heroicon-m-clock',
            self::COVERED => 'heroicon-m-check-badge',
        };
    }
}
