<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use App\Settings\SiteSettings;
use Filament\Pages\SettingsPage;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class ManageSite extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = SiteSettings::class;

    protected static ?string $navigationGroup = 'Administration & Management';

    protected static ?int $navigationSort = 14;

    public static function canAccess(): bool
    {
        return auth()->user()->can('view settings');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->inlineLabel()
                            ->disabled(! auth()->user()->can('edit settings'))
                            ->required(),
                    ])->columns(1),
            ]);
    }
}
