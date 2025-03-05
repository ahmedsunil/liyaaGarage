<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Support\Enums\TransactionType;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\SaleResource;
use Filament\Resources\RelationManagers\RelationManager;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

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
                Tables\Columns\TextColumn::make('vehicle.vehicle_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')->label('Transaction Type')->badge(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('mvr')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options(TransactionType::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                EditAction::make()
                    ->url(fn (Model $record): string => SaleResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
