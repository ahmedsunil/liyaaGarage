<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Brand;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view brands');
    }

    public function view(User $user, Brand $brand): bool
    {
        if ($user->can('view any brand')) {
            return true;
        }

        return $user->can('view brands');
    }

    public function create(User $user): bool
    {
        return $user->can('edit brands');
    }

    public function update(User $user, Brand $brand): bool
    {
        if ($user->can('edit any brand')) {
            return true;
        }

        return $user->can('edit brands');
    }

    public function delete(User $user, Brand $brand): bool
    {
        if ($user->can('delete any brand')) {
            return true;
        }

        return $user->can('delete brands');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any brand');
    }

    public function restore(User $user, Brand $brand): bool
    {
        return $user->can('force delete any brand');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any brand');
    }

    public function forceDelete(User $user, Brand $brand): bool
    {
        return $user->can('force delete any brand');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any brand');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any brand');
    }
}
