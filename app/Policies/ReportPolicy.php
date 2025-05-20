<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view reports');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        if ($user->can('view any report')) {
            return true;
        }

        return $user->can('view reports');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('edit reports');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        if ($user->can('edit any report')) {
            return true;
        }

        return $user->can('edit reports');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        if ($user->can('delete any report')) {
            return true;
        }

        return $user->can('delete reports');
    }

    /**
     * Determine whether the user can delete multiple models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete any report');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Report $report): bool
    {
        return $user->can('force delete any report');
    }

    /**
     * Determine whether the user can restore multiple models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('force delete any report');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $user->can('force delete any report');
    }

    /**
     * Determine whether the user can permanently delete multiple models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force delete any report');
    }
}
