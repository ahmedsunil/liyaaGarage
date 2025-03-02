<?php

namespace App\Support\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionType: string implements HasColor, HasIcon, HasLabel
{
    case CREDIT = 'credit';
    case CASH = 'cash';
    case TRANSFER = 'transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREDIT => 'Credit',
            self::CASH => 'Cash',
            self::TRANSFER => 'Transfer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CREDIT => 'danger',
            self::CASH => 'info',
            self::TRANSFER => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CREDIT => 'heroicon-m-book-open',
            self::CASH => 'heroicon-m-banknotes',
            self::TRANSFER => 'heroicon-m-credit-card',
        };
    }
}
