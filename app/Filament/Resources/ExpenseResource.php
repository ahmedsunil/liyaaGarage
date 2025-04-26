<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Vendor;
use App\Models\Expense;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use App\Filament\Resources\ExpenseResource\Pages;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Core Business Operations';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)
                ->schema([
                    Forms\Components\Section::make()
                        ->columnSpan(8) // Span 9 columns
                        ->schema([
                            Forms\Components\Select::make('expense_type')
                                ->options([
                                    'spare_parts' => 'Spare Parts',
                                    'tools' => 'Tools',
                                    'utility_bills' => 'Utility Bills',
                                ])->required(),
                            Forms\Components\DatePicker::make('date')->required(),
                            Forms\Components\Textarea::make('description'),
                            Forms\Components\Select::make('payment_method')
                                ->options([
                                    'cash' => 'Cash',
                                    'bank_transfer' => 'Bank Transfer',
                                    'card' => 'Card',
                                ])->required(),
                            Forms\Components\Select::make('vendor_id')
                                ->relationship('vendors', 'name')
                                ->searchable()
                                ->preload()
                                ->label('Vendor')
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->label('Name'),
                                    Forms\Components\TextInput::make('address')->label('Address'),
                                    Forms\Components\TextInput::make('phone')->label('Phone Number')->numeric(),
                                    Forms\Components\TextInput::make('email')->label('Email')->email(),
                                ])
//                                ->options(Vendor::pluck('name',
//                                    'id'))  // Key-value pairs where key is the ID
                                ->required(),
                            Forms\Components\TextInput::make('invoice_number')->required(),
                            Forms\Components\Select::make('category')
                                ->options([
                                    'inventory' => 'Inventory',
                                    'operational_expenses' => 'Operational Expenses',
                                    'employee_related' => 'Employee-Related',
                                    'vehicle_maintenance' => 'Vehicle Maintenance',
                                    'marketing' => 'Marketing',
                                    'professional_services' => 'Professional Services',
                                    'insurance' => 'Insurance',
                                    'taxes_and_licenses' => 'Taxes and Licenses',
                                    'vehicle_related' => 'Vehicle-Related',
                                    'miscellaneous' => 'Miscellaneous',
                                ])->required(),
                            Forms\Components\FileUpload::make('attachment')->label('Upload File (PDFs, JPG of Recipets, Invoices etc)'),
                            Forms\Components\Textarea::make('notes')->required(),
                        ]),

                    Forms\Components\Section::make()
                        ->columnSpan(4) // Span 9 columns
                        ->schema([
                            Forms\Components\TextInput::make('rate')
                                ->numeric()
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $qty = $get('qty') ?? 0;
                                    $rate = $get('rate') ?? 0;
                                    $gst = $get('gst') ?? 0;

                                    // Calculate unit_price
                                    $unitPrice = (float) $rate + (float) $gst;
                                    $set('unit_price_with_gst', number_format($unitPrice, 2));

                                    // Calculate total_expenses
                                    $totalExpense = (float) $qty * (float) $unitPrice;
                                    $set('total_expenses', number_format($totalExpense, 2));
                                }),
                            Forms\Components\TextInput::make('qty')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $qty = $get('qty') ?? 0;
                                    $rate = $get('rate') ?? 0;
                                    $gst = $get('gst') ?? 0;

                                    // Calculate unit_price
                                    $unitPrice = (float) $rate + (float) $gst;
                                    $set('unit_price_with_gst', number_format($unitPrice, 2));

                                    // Calculate total_expenses
                                    $totalExpense = (float) $qty * (float) $unitPrice;
                                    $set('total_expenses', number_format($totalExpense, 2));
                                }),

                            Forms\Components\TextInput::make('gst')->numeric()->label('GST')
                                ->live(onBlur: true)
                                ->required()
                                ->default(0)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $qty = $get('qty') ?? 0;
                                    $rate = $get('rate') ?? 0;
                                    $gst = $get('gst') ?? 0;

                                    // Calculate unit_price
                                    $unitPrice = (float) $rate + (float) $gst;
                                    $set('unit_price_with_gst', number_format($unitPrice, 2));

                                    // Calculate total_expenses
                                    $totalExpense = (float) $qty * (float) $unitPrice;
                                    $set('total_expenses', number_format($totalExpense, 2));
                                }),
                            Forms\Components\TextInput::make('unit_price_with_gst')->live()->label('Unit Price (including GST)'),
                            Forms\Components\TextInput::make('total_expenses')->live()->label('Total Expense')->readOnly(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_type'),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\TextColumn::make('date'),
                Tables\Columns\TextColumn::make('amount'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Method to calculate unit_price_with_gst

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // Method to calculate total_expenses

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
