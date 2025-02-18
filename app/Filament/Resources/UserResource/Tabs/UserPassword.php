<?php

namespace App\Filament\Resources\UserResource\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

class UserPassword
{

    public function __invoke()
    {
        return Tabs\Tab::make('Password Change')
                       ->icon('heroicon-o-key')
                       ->schema(static::getPasswordFields());
    }

    public static function getPasswordFields(): array
    {
        return [
            Forms\Components\TextInput::make('password')
                                      ->inlineLabel()
                                      ->password()
                                      ->minLength(8)
                                      ->confirmed()
                                      ->requiredWith('password_confirmation')
                                      ->maxLength(255),
            Forms\Components\TextInput::make('password_confirmation')
                                      ->inlineLabel()
                                      ->password()
                                      ->dehydrated(false)
                                      ->same('password')
                                      ->requiredWith('password')
                                      ->maxLength(255),
        ];
    }
}
