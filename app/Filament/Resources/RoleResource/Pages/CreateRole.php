<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove permissions from the data before creating the role
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Get the permissions from the form data
        $permissions = $this->data['permissions'] ?? [];

        // Sync the permissions with the role
        if (!empty($permissions)) {
            $this->record->syncPermissions($permissions);
        }
    }
}
