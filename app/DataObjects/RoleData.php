<?php

namespace App\DataObjects;

class RoleData
{
    public function __construct(
        public string | null $name,
        public string | null $description,
        public array | null $permissions = []
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: data_get($data, 'name'),
            description: data_get($data, 'description'),
            permissions: data_get($data, 'permissions', [])
        );
    }
}
