<?php

namespace App\Filament\Resources;

use App\Models\Business;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\BusinessResource\Pages\EditBusiness;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static ?string $title = 'Business Information';
    protected static ?string $navigationLabel = 'Business Information';


    protected static ?string $navigationGroup = 'Site Management';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                self::getFormSchema()
            );
    }

    public static function getFormSchema(): array
    {
        return [
            Grid::make()
                ->schema([
                    Section::make('Company Information')
                           ->columnSpan(['lg' => 1])  // Takes one column on large screens
                           ->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('street_address')->required(),
                            TextInput::make('contact')->required(),
                            TextInput::make('invoice_number_prefix')->required(),
                            TextInput::make('email')->email()->required(),
                            FileUpload::make('logo_path')
                                      ->image()
                                      ->directory('logos'),
                        ]),

                    Section::make('Account Details')
                           ->columnSpan(['lg' => 1])  // Takes one column on large screens
                           ->schema([
                            Select::make('account_type')->options([
                                'bml' => 'BML',
                                'mib' => 'MIB',
                            ])->required(),
                            TextInput::make('account_name')->required(),
                            TextInput::make('account_number')->required(),
                        ]),

                    Section::make('Footer Content')
                           ->columnSpan(['lg' => 1])  // Takes one column on large screens
                           ->schema([
                            TextInput::make('footer_text')->required(),
                            TextInput::make('copyright')->required(),
                        ]),
                ])
                ->columns(3),  // Total columns in the grid
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => EditBusiness::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
