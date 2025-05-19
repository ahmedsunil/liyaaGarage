<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view customers');
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->can('view any customer')) {
            return true;
        }

        return $user->can('view customers');
    }

    public function create(User $user): bool
    {
        return $user->can('edit customers');
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->can('edit any customer')) {
            return true;
        }

        return $user->can('edit customers');
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($user->can('delete any customer')) {
            return true;
        }

        return $user->can('delete customers');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any customer');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('force delete any customer');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any customer');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->can('force delete any customer');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any customer');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any customer');
    }
}
