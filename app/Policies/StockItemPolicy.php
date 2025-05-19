<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockItem;

class StockItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view stock items');
    }

    public function view(User $user, StockItem $stockItem): bool
    {
        if ($user->can('view any stock item')) {
            return true;
        }

        return $user->can('view stock items');
    }

    public function create(User $user): bool
    {
        return $user->can('edit stock items');
    }

    public function update(User $user, StockItem $stockItem): bool
    {
        if ($user->can('edit any stock item')) {
            return true;
        }

        return $user->can('edit stock items');
    }

    public function delete(User $user, StockItem $stockItem): bool
    {
        if ($user->can('delete any stock item')) {
            return true;
        }

        return $user->can('delete stock items');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any stock item');
    }

    public function restore(User $user, StockItem $stockItem): bool
    {
        return $user->can('force delete any stock item');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any stock item');
    }

    public function forceDelete(User $user, StockItem $stockItem): bool
    {
        return $user->can('force delete any stock item');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any stock item');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any stock item');
    }
}
