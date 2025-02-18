<?php

namespace App\Filament\Resources;

use Spatie\Activitylog\Models\Activity;
use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'Site Management';
    protected static ?int $navigationSort = 505;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('causer')
                                         ->label('User')
                                         ->formatStateUsing(function ($record) {
                                             return $record->causer?->formatted_name;
                                         }),
                Tables\Columns\TextColumn::make('Subject')
                                         ->formatStateUsing(function ($record) {
                                             return $record->subject?->formatted_name;
                                         }),
                Tables\Columns\TextColumn::make('description')
                                         ->label('Event')
                                         ->searchable()
                                         ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                                         ->label('Logged At')
                                         ->searchable()
                                         ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListActivities::route('/'),
            'view'  => Pages\ViewActivity::route('/{record}'),
        ];
    }
}
