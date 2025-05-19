<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QuotationItem;

class QuotationItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view quotation items');
    }

    public function view(User $user, QuotationItem $quotationItem): bool
    {
        if ($user->can('view any quotation item')) {
            return true;
        }

        return $user->can('view quotation items');
    }

    public function create(User $user): bool
    {
        return $user->can('edit quotation items');
    }

    public function update(User $user, QuotationItem $quotationItem): bool
    {
        if ($user->can('edit any quotation item')) {
            return true;
        }

        return $user->can('edit quotation items');
    }

    public function delete(User $user, QuotationItem $quotationItem): bool
    {
        if ($user->can('delete any quotation item')) {
            return true;
        }

        return $user->can('delete quotation items');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete any quotation item');
    }

    public function restore(User $user, QuotationItem $quotationItem): bool
    {
        return $user->can('force delete any quotation item');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any quotation item');
    }

    public function forceDelete(User $user, QuotationItem $quotationItem): bool
    {
        return $user->can('force delete any quotation item');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any quotation item');
    }

    public function approveAny(User $user): bool
    {
        return $user->can('approve any quotation item');
    }
}
