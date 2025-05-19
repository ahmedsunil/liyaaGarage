<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view vendors');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        if ($user->can('view any vendor')) {
            return true;
        }

        return $user->can('view vendors');
    }

    public function create(User $user): bool
    {
        return $user->can('edit vendors');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        if ($user->can('edit any vendor')) {
            return true;
        }

        return $user->can('edit vendors');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        if ($user->can('delete any vendor')) {
            return true;
        }

        return $user->can('delete vendors');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any vendor');
    }

    public function restore(User $user, Vendor $vendor): bool
    {
        return $user->can('force delete any vendor');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any vendor');
    }

    public function forceDelete(User $user, Vendor $vendor): bool
    {
        return $user->can('force delete any vendor');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any vendor');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any vendor');
    }
}
