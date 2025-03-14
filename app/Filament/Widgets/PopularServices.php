<?php

namespace App\Filament\Widgets;

use Str;
use App\Models\SaleItem;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Contracts\Pagination\Paginator;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Pagination\CursorPaginator;

class PopularServices extends BaseWidget
{
    protected static ?string $heading = 'Most Popular Products/Services';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SaleItem::query()
                    ->selectRaw('stock_items.product_name, count(sale_items.id) as total_sales')
                    ->join('stock_items', 'sale_items.stock_item_id', '=', 'stock_items.id')
                    ->groupBy('stock_items.product_name')
                    ->orderByRaw('count(sale_items.id) DESC') // Use the actual expression instead of the alias
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product/Service')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('total_sales')
                    ->label('Total Sales')
                    ->color('success')
                    ->sortable(),
            ])->paginated();
    }

    public function getTableRecordKey(Model $record): string
    {
        // Create unique key from name + sales count
        return Str::slug($record->product_name).'-'.$record->sales_count;
    }

    //    protected function paginateTableQuery(Builder|\Illuminate\Database\Eloquent\Builder $query
    //    ): CursorPaginator|Paginator {
    //        return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    //    }
}
