<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Pos;

class PosPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view pos');
    }

    public function view(User $user, Pos $pos): bool
    {
        if ($user->can('view any pos')) {
            return true;
        }

        return $user->can('view pos');
    }

    public function create(User $user): bool
    {
        return $user->can('edit pos');
    }

    public function update(User $user, Pos $pos): bool
    {
        if ($user->can('edit any pos')) {
            return true;
        }

        return $user->can('edit pos');
    }

    public function delete(User $user, Pos $pos): bool
    {
        if ($user->can('delete any pos')) {
            return true;
        }

        return $user->can('delete pos');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any pos');
    }

    public function restore(User $user, Pos $pos): bool
    {
        return $user->can('force delete any pos');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any pos');
    }

    public function forceDelete(User $user, Pos $pos): bool
    {
        return $user->can('force delete any pos');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any pos');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any pos');
    }
}
