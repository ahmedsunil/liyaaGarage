<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Expense;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view expenses');
    }

    public function view(User $user, Expense $expense): bool
    {
        if ($user->can('view any expense')) {
            return true;
        }

        return $user->can('view expenses');
    }

    public function create(User $user): bool
    {
        return $user->can('edit expenses');
    }

    public function update(User $user, Expense $expense): bool
    {
        if ($user->can('edit any expense')) {
            return true;
        }

        return $user->can('edit expenses');
    }

    public function delete(User $user, Expense $expense): bool
    {
        if ($user->can('delete any expense')) {
            return true;
        }

        return $user->can('delete expenses');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any expense');
    }

    public function restore(User $user, Expense $expense): bool
    {
        return $user->can('force delete any expense');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any expense');
    }

    public function forceDelete(User $user, Expense $expense): bool
    {
        return $user->can('force delete any expense');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any expense');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any expense');
    }
}
