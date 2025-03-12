<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Breakdown';

    //    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $dailyRevenue = Sale::whereDate('created_at', today())->sum('total_amount');
        $weeklyRevenue = Sale::whereBetween('created_at',
            [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount');
        $monthlyRevenue = Sale::whereMonth('created_at', now()->month)->sum('total_amount');
        $yearlyRevenue = Sale::whereYear('created_at', now()->year)->sum('total_amount');

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
