<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Models\Role;
use App\DataObjects\RoleData;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $roleData = RoleData::fromArray($data);
        /* @var Role $record */
        $record->name = $roleData->name;
        $record->description = $roleData->description;
        $record->save();

        $record->syncPermissions($roleData->permissions);

        return $record;
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
