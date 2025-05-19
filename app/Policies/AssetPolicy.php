<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view assets');
    }

    public function view(User $user, Asset $asset): bool
    {
        if ($user->can('view any asset')) {
            return true;
        }

        return $user->can('view assets');
    }

    public function create(User $user): bool
    {
        return $user->can('edit assets');
    }

    public function update(User $user, Asset $asset): bool
    {
        if ($user->can('edit any asset')) {
            return true;
        }

        return $user->can('edit assets');
    }

    public function delete(User $user, Asset $asset): bool
    {
        if ($user->can('delete any asset')) {
            return true;
        }

        return $user->can('delete assets');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any asset');
    }

    public function restore(User $user, Asset $asset): bool
    {
        return $user->can('force delete any asset');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any asset');
    }

    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->can('force delete any asset');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any asset');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any asset');
    }
}
