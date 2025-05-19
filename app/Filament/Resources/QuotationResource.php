<?php

namespace App\Filament\Resources;

use Str;
use Exception;
use App\Models\Sale;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Models\Quotation;
use App\Models\StockItem;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use App\Support\Enums\TransactionType;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\QoutationResource\Pages;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationGroup = 'Core Business Operations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make('Quotation Items')
                            ->schema([
                                Repeater::make('quotationItems')
                                    ->relationship('quotationItems')
                                    ->label('Products and Services')
                                    ->schema([
                                        Select::make('stock_item_id')
                                            ->label('Product / Service')
                                            ->options(StockItem::query()->pluck('product_name',
                                                'id'))
                                            ->searchable()
                                            ->required()
                                            ->getSearchResultsUsing(function (
                                                string $search
                                            ): array {
                                                return StockItem::query()
                                                    ->where('product_name',
                                                        'like',
                                                        "%{$search}%")
                                                    ->orWhere('item_code',
                                                        'like',
                                                        "%{$search}%")
                                                    ->limit(50)
                                                    ->pluck('product_name',
                                                        'id')
                                                    ->toArray();
                                            })
                                            ->getOptionLabelUsing(function (
                                                $value
                                            ): ?string {
                                                return StockItem::find($value)?->product_name;
                                            })
                                            ->live()
                                            ->afterStateUpdated(function (
                                                $state,
                                                Set $set,
                                                Get $get
                                            ) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $stockItem = StockItem::find($state);
                                                if (! $stockItem) {
                                                    return;
                                                }

                                                $unitPrice = $stockItem->is_service->value
                                                    ? $stockItem->total_cost_price_with_gst
                                                    : $stockItem->selling_price_per_quantity;

                                                $set('unit_price',
                                                    $unitPrice);

                                                if ($stockItem->is_service->value === 1) {
                                                    $set('quantity', 1);
                                                }

                                                // Calculate the total price for this item
                                                $quantity = $stockItem->is_service->value ? 1 : floatval($get('quantity') ?? 1);
                                                $total = round($quantity * $unitPrice,
                                                    2);
                                                $set('total_price', $total);

                                                // Update overall totals using an array instead of a collection
                                                $items = $get('../../quotationItems') ?? [];
                                                foreach ($items as &$item) {
                                                    if ($item['stock_item_id'] === $state) {
                                                        $item['total_price'] = $total;
                                                        break;
                                                    }
                                                }

                                                $subtotal = array_sum(array_column($items,
                                                    'total_price'));
                                                $discountPercentage = floatval($get('../../discount_percentage') ?? 0);
                                                $discountAmount = round(($subtotal * $discountPercentage) / 100,
                                                    2);

                                                $set('../../subtotal_amount',
                                                    $subtotal);
                                                $set('../../discount_amount',
                                                    $discountAmount);
                                                $set('../../total_amount',
                                                    $subtotal - $discountAmount);
                                            }),

                                        // good version
                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->helperText(function (
                                                Get $get
                                            ): string {
                                                $stockItemId = $get('stock_item_id');
                                                $stockItem = StockItem::find($stockItemId);

                                                if ($stockItem && ! $stockItem->is_service->value == 1) {
                                                    return 'Available: '.$stockItem->quantity;
                                                }

                                                return ' ';
                                            })
                                            ->minValue(1)
                                            ->default(1)
                                            ->required()
                                            ->dehydrated()
                                            ->live()
                                            ->disabled(fn (Get $get
                                            ): bool => StockItem::find($get('stock_item_id'))?->is_service->value == '1'
                                            )
                                            ->afterStateUpdated(function (
                                                $state,
                                                Set $set,
                                                Get $get
                                            ) {
                                                // Calculate total price for this item
                                                $unitPrice = floatval($get('unit_price') ?? 0);
                                                $quantity = floatval($state ?? 1);
                                                $total = round($quantity * $unitPrice,
                                                    2);
                                                $set('total_price',
                                                    $total);

                                                // Update overall totals
                                                $items = $get('../../quotationItems') ?? [];
                                                $currentState = $get('stock_item_id');

                                                foreach ($items as &$item) {
                                                    if ($item['stock_item_id'] === $currentState) {
                                                        $item['total_price'] = $total;
                                                        break;
                                                    }
                                                }

                                                $subtotal = array_sum(array_column($items,
                                                    'total_price'));
                                                $discountPercentage = floatval($get('../../discount_percentage') ?? 0);
                                                $discountAmount = round(($subtotal * $discountPercentage) / 100,
                                                    2);

                                                $set('../../subtotal_amount',
                                                    $subtotal);
                                                $set('../../discount_amount',
                                                    $discountAmount);
                                                $set('../../total_amount',
                                                    $subtotal - $discountAmount);
                                            }),

                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->prefix('MVR')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(true),

                                        TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->prefix('MVR')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(true),
                                    ])
                                    ->columns(4)
                                    ->columnSpan(9)
                                    ->live(onBlur: true)
                                    ->required()
                                    ->afterStateUpdated(function (
                                        Set $set,
                                        Get $get
                                    ) {
                                        self::calculateTotals($set, $get);
                                    }),
                            ])->columnSpan(9),

                        Section::make('Quotation Details')
                            ->schema([
                                DatePicker::make('date')
                                    ->required()
                                    ->default(now()),

                                Select::make('customer_id')->label('Customer / Owner')
                                    ->searchable()
                                    ->relationship('customer')
                                    ->getSearchResultsUsing(fn (
                                        string $search
                                    ): array => Customer::where('name',
                                        'like',
                                        "%{$search}%")->orWhere('phone',
                                            'like',
                                            "%{$search}%")->limit(50)->pluck('name',
                                                'id')->toArray())
                                    ->getOptionLabelUsing(fn (
                                        $value
                                    ): ?string => Customer::find($value)?->name)
                                    ->required()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('phone')->required(),
                                        TextInput::make('email')->required(),
                                    ]),


                                Select::make('vehicle_id')
                                    ->label('Vehicle')
                                    ->relationship('vehicle', 'vehicle_number')
                                    ->searchable() // Allows searching through all vehicles
                                    ->preload() // Loads some options upfront for better performance
                                    ->createOptionForm([
                                        Select::make('vehicle_type')
                                            ->options([
                                                'motocycle' => 'Motocycle',
                                                'scooter' => 'Scooter',
                                                'bicycle' => 'Bicycle',
                                                'car' => 'Car',
                                                'tricycle' => 'Tricycle',
                                                'island_pickup' => 'Island Pickup',
                                                'pickup' => 'Pickup',
                                                'buggy' => 'Buggy',
                                                'wheel_barrow' => 'Wheel Barrow',
                                            ])->label('Vehicle Type')->required(),

                                        Select::make('brand_id')
                                            ->relationship('brand',
                                                'name')->required()
                                            ->createOptionForm([
                                                TextInput::make('name')->label('Name')->live(onBlur: true)
                                                    ->afterStateUpdated(fn (
                                                        Set $set,
                                                        ?string $state
                                                    ) => $set('slug',
                                                        Str::slug($state))),
                                                TextInput::make('slug')->label('Slug')->unique('brands',
                                                    'slug',
                                                    ignoreRecord: true)->required()->maxLength(255)->readOnly(),
                                            ]),

                                        TextInput::make('year_of_manufacture')->label('Year of Manufacture')->placeholder('2019')->required(),
                                        TextInput::make('engine_number')->placeholder('Example: PJ12345U123456P'),
                                        TextInput::make('chassis_number')->placeholder('Example: 1HGCM82633A123456'),
                                        TextInput::make('vehicle_number')->placeholder('Example: P9930')->required(),

                                        Select::make('customer_id')->label('Customer / Owner')
                                            ->searchable()->required()
                                            ->getSearchResultsUsing(fn (
                                                string $search
                                            ): array => Customer::where('name',
                                                'like',
                                                "%{$search}%")->orWhere('phone',
                                                    'like',
                                                    "%{$search}%")->limit(50)->pluck('name',
                                                        'id')->toArray())
                                            ->getOptionLabelUsing(fn (
                                                $value
                                            ): ?string => Customer::find($value)?->name),
                                    ]),

                                TextInput::make('subtotal_amount')
                                    ->label('Subtotal')
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->live(),

                                TextInput::make('discount_percentage')
                                    ->label('Discount %')
                                    ->suffix('%')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function (
                                        $state,
                                        Set $set,
                                        Get $get
                                    ) {
                                        self::calculateTotals($set,
                                            $get);
                                    }),

                                TextInput::make('discount_amount')
                                    ->label('Discount Amount')
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->prefix('MVR')
                                    ->disabled()
                                    ->dehydrated(),
                            ])->columnSpan(3),
                    ]),
            ]);
    }

    protected static function calculateTotals(Set $set, Get $get): void
    {
        $subtotal = collect($get('quotationItems'))->sum(function ($item) {
            if (empty($item['stock_item_id'])) {
                return 0;
            }

            return floatval($item['total_price'] ?? 0);
        });

        $discountPercentage = floatval($get('discount_percentage') ?? 0);
        $discountAmount = round(($subtotal * $discountPercentage) / 100, 2);

        $set('subtotal_amount', round($subtotal, 2));
        $set('discount_amount', $discountAmount);
        $set('total_amount', round($subtotal - $discountAmount, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.vehicle_number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('mvr')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options(TransactionType::class),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cloneToSale')
                    ->label('Create Sale')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->action(function (Quotation $record) {
                        // Assuming you have a SaleResource and Sale model
                        // First, we get all the quotation data we need
                        $saleData = [
                            'customer_id' => $record->customer_id,
                            'vehicle_id' => $record->vehicle_id,
                            'date' => now(),
                            'subtotal_amount' => $record->subtotal_amount,
                            'discount_percentage' => $record->discount_percentage,
                            'discount_amount' => $record->discount_amount,
                            'total_amount' => $record->total_amount,
                            'transaction_type' => TransactionType::PENDING,
                            // Update based on your enum
                            'quotation_id' => $record->id,
                            // Reference to the original quotation
                        ];

                        // Create a new sale
                        // Create the sale with validation
                        $sale = Sale::create($saleData);

                        // Validate and convert items
                        if ($record->quotationItems->isEmpty()) {
                            throw new Exception('Cannot create sale: Quotation has no items.');
                        }

                        foreach ($record->quotationItems as $item) {
                            $sale->items()->create([
                                'stock_item_id' => $item->stock_item_id,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'total_price' => $item->total_price,
                            ]);
                        }

                        // Redirect to the edit page of the newly created sale
                        return redirect()->to(SaleResource::getUrl('edit', ['record' => $sale]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Create Sale from Quotation')
                    ->modalDescription('Are you sure you want to create a new sale from this quotation? All quotation data will be copied to the sale.')
                    ->modalSubmitActionLabel('Yes, Create Sale'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('print')
                        ->label('Generate Quotations')
                        ->icon('heroicon-o-document-text')
                        ->action(function (Collection $records) {
                            $pdf = PDF::loadView('pdf.quotation', [
                                'quotations' => $records,
                            ]);

                            // Get first sale's customer info for filename
                            $firstSale = $records->first();
                            $filename = str_replace(' ', '_', strtolower($firstSale->customer->name))
                                .'_'
                                .$firstSale->customer->phone
                                .'.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename);
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListQoutations::route('/'),
            'create' => Pages\CreateQoutation::route('/create'),
            'edit' => Pages\EditQoutation::route('/{record}/edit'),
        ];
    }
}
