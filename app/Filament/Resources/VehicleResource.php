<?php

namespace App\Filament\Resources;

use Str;
use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\VehicleResource\Pages;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehicle_type')
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
                    ])->label('Vehicle Type'),
                Forms\Components\Select::make('brand')
                    ->options([
                        'honda' => 'Honda',
                        'suzuki' => 'Suzuki',
                        'bmx' => 'BMX',
                        'other' => 'Other',
                    ]),
                Forms\Components\TextInput::make('year_of_manufacture')->label('Year of Manufacture')->placeholder('2019'),
                Forms\Components\TextInput::make('engine_number')->placeholder('Example: PJ12345U123456P'),
                Forms\Components\TextInput::make('chassis_number')->placeholder('Example: 1HGCM82633A123456'),
                Forms\Components\TextInput::make('vehicle_number')->placeholder('Example: P9930'),
                Forms\Components\Select::make('customer_id')->label('Customer / Owner')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Customer::where('name',
                        'like', "%{$search}%")->orWhere('phone', 'like',
                            "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => Customer::find($value)?->name),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle_type')->formatStateUsing(function ($state) {
                    return Str::title($state);
                }),
                Tables\Columns\TextColumn::make('brand')->formatStateUsing(function ($state) {
                    return Str::title($state);
                }),
                Tables\Columns\TextColumn::make('vehicle_number'),
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
