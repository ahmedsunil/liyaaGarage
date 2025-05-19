<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sale;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view sales');
    }

    public function view(User $user, Sale $sale): bool
    {
        if ($user->can('view any sale')) {
            return true;
        }

        return $user->can('view sales');
    }

    public function create(User $user): bool
    {
        return $user->can('edit sales');
    }

    public function update(User $user, Sale $sale): bool
    {
        if ($user->can('edit any sale')) {
            return true;
        }

        return $user->can('edit sales');
    }

    public function delete(User $user, Sale $sale): bool
    {
        if ($user->can('delete any sale')) {
            return true;
        }

        return $user->can('delete sales');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any sale');
    }

    public function restore(User $user, Sale $sale): bool
    {
        return $user->can('force delete any sale');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any sale');
    }

    public function forceDelete(User $user, Sale $sale): bool
    {
        return $user->can('force delete any sale');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any sale');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any sale');
    }
}
