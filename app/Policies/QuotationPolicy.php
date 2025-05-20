<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Quotation;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view quotation');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        if ($user->can('view any quotation')) {
            return true;
        }

        return $user->can('view quotations');
    }

    public function create(User $user): bool
    {
        return $user->can('edit quotations');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        if ($user->can('edit any quotation')) {
            return true;
        }

        return $user->can('edit quotations');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        if ($user->can('delete any quotation')) {
            return true;
        }

        return $user->can('delete quotations');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any quotation');
    }

    public function restore(User $user, Quotation $quotation): bool
    {
        return $user->can('force delete any quotation');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any quotation');
    }

    public function forceDelete(User $user, Quotation $quotation): bool
    {
        return $user->can('force delete any quotation');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any quotation');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any quotation');
    }
}
