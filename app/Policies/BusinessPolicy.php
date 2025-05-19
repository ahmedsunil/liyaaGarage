<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Business;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view businesses');
    }

    public function view(User $user, Business $business): bool
    {
        if ($user->can('view any business')) {
            return true;
        }

        return $user->can('view businesses');
    }

    public function create(User $user): bool
    {
        return $user->can('edit businesses');
    }

    public function update(User $user, Business $business): bool
    {
        if ($user->can('edit any business')) {
            return true;
        }

        return $user->can('edit businesses');
    }

    public function delete(User $user, Business $business): bool
    {
        if ($user->can('delete any business')) {
            return true;
        }

        return $user->can('delete businesses');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any business');
    }

    public function restore(User $user, Business $business): bool
    {
        return $user->can('force delete any business');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any business');
    }

    public function forceDelete(User $user, Business $business): bool
    {
        return $user->can('force delete any business');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any business');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any business');
    }
}
