<?php

namespace App\Filament\Resources;

use Str;
use Filament\Forms;
use Filament\Tables;
use App\Models\Asset;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Support\Enums\AssetStatuses;
use App\Filament\Resources\AssetResource\Pages;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Resources & Infrastructure';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name'),
                        Forms\Components\Select::make('type')->options([
                            'tools' => 'Tools',
                            'equipments' => 'Equipments',
                            'others' => 'Others',
                        ]),
                        Forms\Components\DatePicker::make('purchased_date'),
                        Forms\Components\TextInput::make('purchased_price'),
                        Forms\Components\Textarea::make('description'),
                        Forms\Components\Select::make('status')->options(AssetStatuses::class),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type')->formatStateUsing(function ($state) {
                    return Str::title($state);
                }),
                Tables\Columns\TextColumn::make('purchased_date')->label('Purchased On')->date(),
                Tables\Columns\TextColumn::make('purchased_price')->label('Purchased Price'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('status')->badge(),
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
            'index' => Pages\ListAssets::route('/'),
            //            'create' => Pages\CreateAsset::route('/create'),
            //            'edit'   => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
