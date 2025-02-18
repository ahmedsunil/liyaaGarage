<?php

namespace App\Filament\Resources\UserResource\Tabs;

use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Support\Enums\UserStatuses;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class Profile
{
    public function __invoke()
    {
        return Tabs\Tab::make('Profile')
                       ->icon('heroicon-o-user')
                       ->schema(static::getProfileFields());
    }

    public static function getProfileFields(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('avatar')
                                        ->inlineLabel()
                                        ->collection('avatar'),
            Forms\Components\TextInput::make('name')
                                      ->inlineLabel()
                                      ->string()
                                      ->required()
                                      ->maxLength(255),
            Forms\Components\TextInput::make('email')
                                      ->inlineLabel()
                                      ->string()
                                      ->email()
                                      ->unique('users', 'email', ignoreRecord: true)
                                      ->required()
                                      ->maxLength(255),

            Forms\Components\Select::make('role')
                                   ->inlineLabel()
                                   ->visible(fn() => auth()->user()->can('approveAny', User::class))
                                   ->options(Role::all()->pluck('description', 'id')->toArray())
                                   ->afterStateHydrated(fn($set, $record) => $set('role', $record?->roles->first()?->id))
                                   ->searchable()
                                   ->preload(),
            Forms\Components\Select::make('status')
                                   ->inlineLabel()
                                   ->visible(auth()->user()->can('edit user role'))
                                   ->options(UserStatuses::labels())
                                   ->searchable(),
            Forms\Components\DateTimePicker::make('email_verified_at')
                                           ->inlineLabel()
                                           ->date()
                                           ->disabled(fn() => ! auth()->user()->can('approveAny', User::class))
                                           ->dehydrated(fn() => auth()->user()->can('approveAny', User::class)),
            Forms\Components\TextInput::make('contact_number')
                                      ->inlineLabel()
                                      ->numeric()
                                      ->minLength(7)
                                      ->nullable()
                                      ->maxLength(255),

        ];
    }
}
