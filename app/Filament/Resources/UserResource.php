<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\Tabs\Profile;
use App\Filament\Resources\UserResource\Tabs\UserPassword;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 405;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Label')
                    ->tabs([
                        (new Profile)(),
                        (new UserPassword)(),
                    ])
                    ->persistTabInQueryString()
                    ->columns(1),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                                         ->searchable(),
                Tables\Columns\TextColumn::make('email')
                                         ->icon(function ($record) {
                                             if ($record->email_verified_at) {
                                                 return 'heroicon-o-check-circle';
                                             }
                                             return 'heroicon-o-x-circle';
                                         })
                                         ->iconColor(function ($record) {
                                             return $record->email_verified_at ? 'success' : 'danger';
                                         })
                                         ->copyable()
                                         ->copyMessage('Email address copied')
                                         ->searchable(),
                Tables\Columns\TextColumn::make('roles.description')
                                         ->url(fn(User $record): string => $record->roles->isNotEmpty() ? route('filament.admin.resources.roles.edit', $record->roles?->first()) : "#"),
                Tables\Columns\TextColumn::make('status')
                                         ->badge()
                                         ->formatStateUsing(fn(User $record): string => $record->status->getLabel())
                                         ->color(fn(User $record): string => $record->status->getColor()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                                           ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
                     ->withoutGlobalScopes([
                         SoftDeletingScope::class,
                     ]);
    }
}
