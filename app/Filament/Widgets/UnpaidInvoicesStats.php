<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class UnpaidInvoicesStats extends BaseWidget
{
    protected static ?int $sort = -2; // Position at the top

    protected ?string $heading = 'Unpaid Invoices & Total Sales';

    protected string|int|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalUnpaid = Sale::where('transaction_type', 'pending')->sum('total_amount');
        $overdueCount = Sale::where('transaction_type', 'pending')
            ->where('created_at', '<', now())
            ->count();
        $totalSales = Sale::count();


        return [
            Stat::make('Total Unpaid Amount', '$'.number_format($totalUnpaid))
                ->description('Total amount due from unpaid sales')
                ->icon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('Overdue Sales', (string) $overdueCount)
                ->description('Number of sales past their due date')
                ->icon('heroicon-o-clock')
                ->color('danger'),

            Stat::make('Total Number of Sales', (string) $totalSales)
                ->description('Total number of sales we made')
                ->icon('heroicon-o-chart-bar-square')
                ->color('danger'),
        ];
    }
}
