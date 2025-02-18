<?php

namespace App\Filament\Resources;

use App\Models\Role;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;
use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Components\Forms\PermissionSelector;
use App\Filament\Resources\RoleResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 410;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                       ->schema([

                           Forms\Components\TextInput::make('name')
                                                     ->unique('roles', 'name', ignoreRecord: true)
                                                     ->required()
                                                     ->maxLength(255),
                           Forms\Components\TextInput::make('description')
                                                     ->maxLength(255),
                           PermissionSelector::make('permissions')
                               ->selectedOptions(fn(Role $role) => $role->permissions->pluck('id')->toArray())
                               ->options(function () {
                                   return Permission::getPermissionModels();
                               }),
//                           Forms\Components\CheckboxList::make('permissions')
//                                                        ->relationship(
//                                                            name: 'permissions',
//                                                            titleAttribute: 'name',
//                                                            modifyQueryUsing: fn(Builder $query) => $query->orderBy('model', 'ASC')
//                                                        )
//                                                        ->getOptionLabelFromRecordUsing(fn(Model $record) => str($record->name)->title())
//                                                        ->gridDirection('row')
//                                                        ->columns(2)
//                                                        ->bulkToggleable()
//                                                        ->searchable(),
                       ])
                       ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('guard_name'),
                Tables\Columns\TextColumn::make('permissions_count')->label('Permissions')->counts('permissions'),
                Tables\Columns\TextColumn::make('description'),
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
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
