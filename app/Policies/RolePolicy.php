<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;

class RolePolicy
{
    public function  viewAny(User $user): bool
    {
        return $user->can('view roles');
    }

    public function view(User $user, Role $role): bool
    {
        if ($user->can('view any role')) {
            return true;
        }

        return $user->can('view roles') && $user->roles->first()?->id === $role->id;
    }

    public function create(User $user): bool
    {
        return $user->can('edit role');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('edit role');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('delete role');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any role');
    }
}
