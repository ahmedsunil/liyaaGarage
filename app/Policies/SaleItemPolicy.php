<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SaleItem;

class SaleItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view sale items');
    }

    public function view(User $user, SaleItem $saleItem): bool
    {
        if ($user->can('view any sale item')) {
            return true;
        }

        return $user->can('view sale items');
    }

    public function create(User $user): bool
    {
        return $user->can('edit sale items');
    }

    public function update(User $user, SaleItem $saleItem): bool
    {
        if ($user->can('edit any sale item')) {
            return true;
        }

        return $user->can('edit sale items');
    }

    public function delete(User $user, SaleItem $saleItem): bool
    {
        if ($user->can('delete any sale item')) {
            return true;
        }

        return $user->can('delete sale items');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any sale item');
    }

    public function restore(User $user, SaleItem $saleItem): bool
    {
        return $user->can('force delete any sale item');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any sale item');
    }

    public function forceDelete(User $user, SaleItem $saleItem): bool
    {
        return $user->can('force delete any sale item');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any sale item');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any sale item');
    }
}
