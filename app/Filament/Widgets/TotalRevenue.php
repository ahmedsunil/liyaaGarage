<?php

namespace App\Filament\Widgets;

use App\Models\Pos;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as Widget;

class TotalRevenue extends Widget
{
    protected static ?int $sort = -2; // Position right after UnpaidInvoicesStats

    protected function getStats(): array
    {
        $totalRevenue = Pos::where('transaction_type', '!=', 'pending')
            ->sum('total_amount');

        $monthlyRevenue = Pos::whereMonth('created_at', now()->month)
            ->where('transaction_type', '!=', 'pending')
            ->sum('total_amount');

        $yearlyRevenue = Pos::whereYear('created_at', now()->year)
            ->where('transaction_type', '!=', 'pending')
            ->sum('total_amount');

        return [
            Stat::make('Total Revenue', number_format($totalRevenue, 2))
                ->description('All time revenue')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Monthly Revenue', number_format($monthlyRevenue, 2))
                ->description('This month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Yearly Revenue', number_format($yearlyRevenue, 2))
                ->description('This year')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
        ];
    }
}
