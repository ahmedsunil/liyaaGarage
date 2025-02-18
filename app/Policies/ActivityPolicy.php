<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Permission;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view logs');
    }

    public function view(User $user, Activity $activity): bool
    {
        if ($user->can('view any log')) {
            return true;
        }

        if (! $user->can('view logs')) {
            return false;
        }

        return $user->can('view', $activity->subject);
    }
}
