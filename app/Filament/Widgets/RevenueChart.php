<?php

namespace App\Filament\Widgets;

use App\Models\Pos;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Breakdown';

    //    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $dailyRevenue = Pos::whereDate('created_at', today())
            ->where('transaction_type', '!=', 'pending')
            ->sum('total_amount');

        $weeklyRevenue = Pos::whereBetween('created_at',
            [now()->startOfWeek(), now()->endOfWeek()])
            ->where('transaction_type', '!=', 'pending')
            ->sum('total_amount');

        $monthlyRevenue = Pos::whereMonth('created_at', now()->month)
            ->where('transaction_type', '!=', 'pending')
            ->sum('total_amount');

        $yearlyRevenue = Pos::whereYear('created_at', now()->year)
            ->where('transaction_type', '!=', 'pending')
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => [$dailyRevenue, $weeklyRevenue, $monthlyRevenue, $yearlyRevenue],
                ],
            ],
            'labels' => ['Daily', 'Weekly', 'Monthly', 'Yearly'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
