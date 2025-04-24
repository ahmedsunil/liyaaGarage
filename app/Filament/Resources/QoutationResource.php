<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Qoutation;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\QoutationResource\Pages;

class QoutationResource extends Resource
{
    protected static ?string $model = Qoutation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationGroup = 'Core Business Operations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListQoutations::route('/'),
            'create' => Pages\CreateQoutation::route('/create'),
            'edit' => Pages\EditQoutation::route('/{record}/edit'),
        ];
    }
}
