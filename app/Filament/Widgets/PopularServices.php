<?php

namespace App\Filament\Widgets;

use Str;
use App\Models\Pos;
use App\Models\StockItem;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularServices extends BaseWidget
{
    protected static ?string $heading = 'Most Popular Products/Services';

    public function table(Table $table): Table
    {
        // Get all Pos records with sale_items
        $posRecords = Pos::whereNotNull('sale_items')->get();

        // Extract all stock_item_ids from the sale_items JSON field
        $stockItemCounts = [];
        foreach ($posRecords as $pos) {
            if (! empty($pos->sale_items)) {
                foreach ($pos->sale_items as $item) {
                    $stockItemId = $item['stock_item_id'];
                    if (! isset($stockItemCounts[$stockItemId])) {
                        $stockItemCounts[$stockItemId] = 0;
                    }
                    $stockItemCounts[$stockItemId]++;
                }
            }
        }

        // Get product names for the stock items
        $stockItems = StockItem::whereIn('id', array_keys($stockItemCounts))->get();
        $productNameMap = $stockItems->pluck('product_name', 'id')->toArray();

        // Create a collection of product names and their counts
        $popularProducts = collect();
        foreach ($stockItemCounts as $stockItemId => $count) {
            $productName = $productNameMap[$stockItemId] ?? 'Unknown Product';
            $popularProducts->push([
                'product_name' => $productName,
                'total_sales'  => $count,
            ]);
        }

        // Sort by total_sales in descending order
        $popularProducts = $popularProducts->sortByDesc('total_sales')->values();

        return $table
            ->query(
            // Use a query builder that works with the collection
                StockItem::query()
                         ->whereIn('id', array_keys($stockItemCounts))
                         ->selectRaw('id, product_name')
                         ->orderByRaw('FIELD(id, '.implode(',', array_keys($stockItemCounts)).')')
                         ->limit(count($stockItemCounts))
            )
            ->modifyQueryUsing(function ($query) {
                // This is a hack to make the table work with our custom data
                // We'll override the data in the columns
                return $query;
            })
            ->columns([
                TextColumn::make('product_name')
                          ->label('Product/Service')
                          ->getStateUsing(function ($record, $rowLoop) use ($popularProducts) {
                              return $popularProducts[$rowLoop->iteration - 1]['product_name'] ?? $record->product_name;
                          }),
                //                    ->searchable(),

                BadgeColumn::make('total_sales')
                           ->label('Total Sales')
                           ->getStateUsing(function ($record, $rowLoop) use ($popularProducts) {
                               return $popularProducts[$rowLoop->iteration - 1]['total_sales'] ?? 0;
                           })
                           ->color('success'),
            ])->paginated();
    }

    public function getTableRecordKey(Model $record): string
    {
        // Create a unique key from name + id
        return Str::slug($record->product_name).'-'.$record->id;
    }
}
