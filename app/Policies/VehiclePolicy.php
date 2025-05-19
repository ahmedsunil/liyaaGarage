<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view vehicles');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('view any vehicle')) {
            return true;
        }

        return $user->can('view vehicles');
    }

    public function create(User $user): bool
    {
        return $user->can('edit vehicles');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('edit any vehicle')) {
            return true;
        }

        return $user->can('edit vehicles');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        if ($user->can('delete any vehicle')) {
            return true;
        }

        return $user->can('delete vehicles');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any vehicle');
    }

    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $user->can('force delete any vehicle');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any vehicle');
    }

    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('force delete any vehicle');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any vehicle');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any vehicle');
    }
}
