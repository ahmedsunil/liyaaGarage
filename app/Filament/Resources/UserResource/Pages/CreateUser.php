<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Forms\Form;
use App\DataObjects\UserData;
use App\Services\UserService;
use App\DataObjects\UserPasswordData;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\UserResource\Tabs\Profile;
use App\Filament\Resources\UserResource\Tabs\UserPassword;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Profile')->schema(Profile::getProfileFields()),
            Section::make('Password')->schema(UserPassword::getPasswordFields()),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user_data = UserData::fromArray($data);
        $passwordData = UserPasswordData::fromArray($data);
        return (new UserService)->updateOrCreate($user_data, password_data: $passwordData);
    }
}
