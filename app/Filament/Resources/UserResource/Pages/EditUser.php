<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\DataObjects\UserData;
use App\Services\UserService;
use App\DataObjects\UserPasswordData;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /* @var User $record */
        $password = $data['password'] ?? null;
        if ($password) {
            $passwordData = UserPasswordData::fromArray($data);
            return (new UserService)->updatePassword($record, $passwordData);
        }

        $profileData = UserData::fromArray($data);
        return (new UserService)->updateOrCreate($profileData, $record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
