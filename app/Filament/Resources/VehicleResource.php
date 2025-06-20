<?php

namespace App\Filament\Resources;

use Str;
use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\VehicleResource\Pages;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Core Business Operations';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('vehicle_type')->label('Vehicle Type')->options([
                            'motorcycle' => 'Motorcycle',
                            'scooter' => 'Scooter',
                            'bicycle_16' => 'Bicycle 16 Inch',
                            'bicycle_20' => 'Bicycle 20 Inch',
                            'bicycle_24' => 'Bicycle 24 Inch',
                            'car' => 'Car',
                            'tricycle' => 'Tricycle',
                            'island_pickup' => 'Island Pickup',
                            'pickup' => 'Pickup',
                            'buggy' => 'Buggy',
                            'wheel_barrow' => 'Wheel Barrow',
                        ])->required()
                            ->rules([
                                Rule::in([
                                    'motorcycle',
                                    'scooter',
                                    'bicycle_16',
                                    'bicycle_20',
                                    'bicycle_24',
                                    'car',
                                    'tricycle',
                                    'island_pickup',
                                    'pickup',
                                    'buggy',
                                    'wheel_barrow',
                                ]),
                            ])->live(),

                        Forms\Components\Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->required()
                            ->rules([
                                'required',
                                'exists:brands,id',
                            ])
                            ->validationMessages([
                                'required' => 'Please select a brand',
                                'exists' => 'The selected brand no longer exists',
                            ])
                            ->createOptionForm([
                                TextInput::make('name')->label('Name')->live(onBlur: true)
                                    ->afterStateUpdated(fn (
                                        Set $set,
                                        ?string $state
                                    ) => $set('slug', Str::slug($state))),
                                TextInput::make('slug')->label('Slug')->unique('brands',
                                    'slug',
                                    ignoreRecord: true)->required()->maxLength(255)->readOnly(),
                            ]),

                        Forms\Components\TextInput::make('year_of_manufacture')
                            ->label('Year of Manufacture')
                            ->placeholder('2019')
                            ->hidden(fn (callable $get
                            ): bool => in_array($get('vehicle_type'), [
                                'bicycle_16', 'bicycle_20', 'bicycle_24',
                                'tricycle', 'wheel_barrow',
                            ])),

                        Forms\Components\TextInput::make('engine_number')
                            ->placeholder('Example: PJ12345U123456P')
                            ->hidden(fn (callable $get
                            ): bool => in_array($get('vehicle_type'), [
                                'bicycle_16', 'bicycle_20', 'bicycle_24',
                                'tricycle', 'wheel_barrow',
                            ])),

                        Forms\Components\TextInput::make('chassis_number')
                            ->placeholder('Example: 1HGCM82633A123456')
                            ->hidden(fn (callable $get
                            ): bool => in_array($get('vehicle_type'), [
                                'bicycle_16', 'bicycle_20', 'bicycle_24',
                                'tricycle', 'wheel_barrow',
                            ])),

                        Forms\Components\TextInput::make('vehicle_number')->label('Plate Number / Vehicle Tag')->placeholder('Example: P9930 OR BC-mobile number')->hint('If it is a bicycle or any other vehicle that doesnt have plate number, use code like WB for wheel barrow Example: WB-mobilenumber'),

                        Forms\Components\Select::make('customer_id')->label('Customer / Owner')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search
                            ): array => Customer::where('name',
                                'like', "%{$search}%")->orWhere('phone', 'like',
                                    "%{$search}%")->limit(50)->pluck('name',
                                        'id')->toArray())
                            ->getOptionLabelUsing(fn ($value
                            ): ?string => Customer::find($value)?->name),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle_type')->formatStateUsing(function ($state) {
                    return Str::title($state);
                }),
                Tables\Columns\TextColumn::make('brand.name')->formatStateUsing(function ($state) {
                    return Str::title($state);
                }),
                Tables\Columns\TextColumn::make('vehicle_number')->label('Plate Number / Vehicle Tag'),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer / Owner')->searchable(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
