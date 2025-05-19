<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Str;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\VehicleResource;
use Filament\Resources\RelationManagers\RelationManager;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('vehicle_type')->formatStateUsing(function ($state) {
                    return Str::title($state);
                }),
                Tables\Columns\TextColumn::make('brand.name')->formatStateUsing(function ($state) {
                    return Str::title($state);
                }),
                Tables\Columns\TextColumn::make('vehicle_number'),
                Tables\Columns\TextColumn::make('year_of_manufacture'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                EditAction::make()
                    ->url(fn (Model $record): string => VehicleResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
