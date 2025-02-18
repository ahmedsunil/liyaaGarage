<?php
namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view users');
    }

    public function view(User $user, User $model): bool
    {
        if ($user->can('view any user')) {
            return true;
        }

        return $user->can('view users') && $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->can('edit users');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->can('edit any user')) {
            return true;
        }

        return $user->can('edit users') && $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->can('delete any user')) {
            return true;
        }

        return $user->can('delete users') && $user->id === $model->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any user');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('force delete any user');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any user');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('force delete any user');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any user');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any user');
    }
}
