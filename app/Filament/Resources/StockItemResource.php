<?php

namespace App\Filament\Resources;

use Exception;
use Filament\Forms;
use Filament\Tables;
use App\Models\Vendor;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\StockItem;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StockItemResource\Pages;

class StockItemResource extends Resource
{
    protected static ?string $model = StockItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $label = 'Product / Service';

    protected static ?string $navigationGroup = 'Core Business Operations';

    protected static ?int $navigationSort = 5;

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_code')
                    ->label('Code')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('is_service')
                    ->label('Type')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_cost_price_with_gst')
                    ->label('Total Cost Price With GST')
                    ->money('MVR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->hidden(fn (Builder $query): bool => $query->where('is_service',
                        true)->exists()),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Item Type')
                    ->options([
                        '0' => 'Products',
                        '1' => 'Services',
                    ])
                    ->attribute('is_service'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Action::make('reAdjustAllStock')
                    ->label('Adjust All Stock')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        // Call a method that updates all stock statuses
                        StockItem::updateAllStockStatuses();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Adjust All Stock')
                    ->modalDescription('Are you sure you want to re-adjust all stock statuses?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('update-quantity')
                        ->label('Update Quantity')
                        ->icon('heroicon-o-plus')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->form([  // Changed from a function to a direct array
                            TextInput::make('quantity')
                                ->label('Quantity to Add')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->rules(['min:1']),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $quantityToAdd = floatval($data['quantity']);

                            $records->each(function ($record) use ($quantityToAdd) {
                                if (! $record->is_service) {
                                    $record->quantity += $quantityToAdd;
                                    $record->save();
                                }
                            });

                            Notification::make()
                                ->success()
                                ->title('Quantities updated successfully')
                                ->send();
                        }),

                    BulkAction::make('deduct-quantity')
                        ->label('Deduct Quantity')
                        ->icon('heroicon-o-minus')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->form([  // Changed from a function to a direct array
                            TextInput::make('quantity')
                                ->label('Quantity to Deduct')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->rules(['min:1']),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $quantityToAdd = floatval($data['quantity']);

                            $records->each(function ($record) use ($quantityToAdd) {
                                if (! $record->is_service) {
                                    $record->quantity -= $quantityToAdd;
                                    $record->save();
                                }
                            });

                            Notification::make()
                                ->success()
                                ->title('Quantities updated successfully')
                                ->send();
                        }),

                ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_service')
                            ->label('Product Type')
                            ->inline()
                            ->live()
                            ->required()
                            ->options([
                                0 => 'Product',
                                1 => 'Service',
                            ])
                            ->colors([
                                0 => 'success',
                                1 => 'warning',
                            ])
                            ->icons([
                                0 => 'heroicon-m-shopping-bag',
                                1 => 'heroicon-m-wrench-screwdriver',
                            ])
                            ->default(0)
                            ->disabled(fn (
                                $livewire
                            ) => $livewire instanceof EditRecord)
                            ->afterStateUpdated(function (
                                Get $get,
                                Set $set
                            ) {
                                $set('total_cost_price',
                                    null);
                                $set('gst',
                                    null);
                                $set('total_cost_price_with_gst',
                                    null);
                                $set('cost_price_per_quantity',
                                    null);
                                $set('selling_price_per_quantity',
                                    null);
                                $set('quantity',
                                    null);
                                $set('quantity_threshold',
                                    null);
                            })->columnSpan(1),

                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('item_code')
                                    ->label(fn (Get $get
                                    ) => $get('is_service') ? 'Service Code' : 'Item Code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(
                                        table: StockItem::class,
                                        column: 'item_code',
                                        ignorable: fn (
                                            $record
                                        ) => $record
                                    )
                                    ->validationMessages([
                                        'unique' => 'This :attribute is already taken.',
                                    ]),

                                Forms\Components\TextInput::make('product_name')
                                    ->label(fn (Get $get
                                    ) => $get('is_service') ? 'Service Name' : 'Product Name')
                                    ->required()
                                    ->maxLength(255),


                                Forms\Components\Select::make('vendor_id')
                                    ->relationship('vendor',
                                        'name')
                                    ->searchable()
                                    ->required()
                                    ->preload()
                                    ->label('Vendor')
//                                    ->options(self::getVendors() ?? [])
                                    ->helperText(empty(self::getVendors()) ? 'No vendors available.' : '')
                                    ->disabled(empty(self::getVendors()))
                                    ->visible(fn (Get $get
                                    ) => ! $get('is_service'))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('Name'),
                                        Forms\Components\TextInput::make('address')->label('Address'),
                                        Forms\Components\TextInput::make('phone')->label('Phone Number')->numeric(),
                                        Forms\Components\TextInput::make('email')->label('Email')->email(),
                                    ]),
                            ])
                            ->columns(),

                        Forms\Components\Section::make('Pricing & Stock')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('total_cost_price')
                                            ->label(function (
                                                Get $get
                                            ) {
                                                if ($get('is_service')) {
                                                    return 'Service Price';
                                                }

                                                return 'Cost Price';
                                            })
                                            ->numeric()
                                            ->step(0.01)
                                            ->default(0)
                                            ->prefix('MVR')
                                            ->required()
                                            ->rules([
                                                'numeric',
                                                'min:0',
                                            ])
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (
                                                Get $get,
                                                Set $set
                                            ) => self::calculateTotal($get,
                                                $set)),

                                        Forms\Components\TextInput::make('gst')
                                            ->label('GST')
                                            ->numeric()
                                            ->default(0)
                                            ->rules([
                                                'numeric',
                                                'min:0',
                                            ])
                                            ->step(0.01)
                                            ->prefix('%')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (
                                                Get $get,
                                                Set $set
                                            ) => self::calculateTotal($get,
                                                $set)),

                                        Forms\Components\TextInput::make('total_cost_price_with_gst')
                                            ->label('Total Cost Price')
                                            ->reactive()
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('MVR')
                                            ->readOnly()
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Stock Quantity')
                                            ->numeric()
                                            ->default(0)
                                            ->visible(fn (
                                                Get $get
                                            ) => ! $get('is_service'))
                                            ->required()
                                            ->live(onBlur: true)
                                            ->rules([
                                                'numeric',
                                                'min:0',
                                            ])
                                            ->afterStateUpdated(fn (
                                                Get $get,
                                                Set $set
                                            ) => self::calculateTotal($get,
                                                $set)),


                                        Forms\Components\TextInput::make('cost_price_per_quantity')
                                            ->label('Cost Price Per Quantity')
                                            ->reactive()
                                            ->default(0)
                                            ->numeric()
                                            ->prefix('MVR')
                                            ->readOnly()
                                            ->dehydrated()
                                            ->visible(fn (
                                                Get $get
                                            ) => ! $get('is_service')),

                                        Forms\Components\TextInput::make('selling_price_per_quantity')
                                            ->label('Selling Price Per Quantity')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('MVR')
                                            ->required()
                                            ->visible(fn (
                                                Get $get
                                            ) => ! $get('is_service')),

                                        Forms\Components\TextInput::make('quantity_threshold')
                                            ->label('Quantity Threshold')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->columns(4)
                                            ->visible(fn (
                                                Get $get
                                            ) => ! $get('is_service')),
                                    ])->columns(3),
                            ]),
                    ]),
            ]);
    }

    public static function getVendors(): \Illuminate\Support\Collection
    {
        return Vendor::whereNotNull('id')->get()->pluck('name', 'id');
    }

    protected static function calculateTotal(Get $get, Set $set): void
    {
        // Retrieve and sanitize input values
        $cost_price = max(0, floatval($get('total_cost_price') ?? 0));
        $gst = max(0, floatval($get('gst') ?? 0));
        $qty = max(1, floatval($get('quantity') ?? 1));

        $isService = boolval($get('is_service'));

        $gst_value = ($gst / 100) * $cost_price;
        // Calculate total
        $total_cost_price = $cost_price + $gst_value;

        $set('total_cost_price_with_gst', $total_cost_price);

        // Calculate Price per quantity
        if (! $isService) {
            $cost_price_per_quantity = $total_cost_price / $qty;
            $set('cost_price_per_quantity', $cost_price_per_quantity);
        }
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockItems::route('/'),
            'create' => Pages\CreateStockItem::route('/create'),
            'edit' => Pages\EditStockItem::route('/{record}/edit'),
        ];
    }
}
