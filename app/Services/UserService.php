<?php

namespace App\Services;

use App\Models\User;
use App\DataObjects\UserData;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\DataObjects\UserPasswordData;

class UserService
{
    public function updateOrCreate(
        UserData $user_data,
        User | null $user = null,
        UserPasswordData $password_data = null
    ): User {
        if (! $user) {
            $user = new User();
        }

        $user->name = $user_data->name;
        $user->email = $user_data->email;
        $user->contact_number = $user_data->contact_number;
        $user->email_verified_at = $user_data->email_verified_at;

        if (auth()->user()->can('approveAny', User::class)) {
            $user->status = $user_data->status;
        }

        if ($password_data) {
            $this->updatePassword($user, $password_data, false);
        }

        $user->save();
        $this->assignRole($user, $user_data);
        return $user;
    }

    public function updatePassword(User $user, UserPasswordData $passwordData, bool $save = true): User
    {
        $user->password = Hash::make($passwordData->password);
        if ($save) {
            $user->save();
        }

        return $user;
    }

    private function assignRole(User $user, UserData $user_data): void
    {
        $has_permission = auth()->user()->can('approveAny', User::class);
        if ($has_permission && $user_data->role) {
            $user->assignRole($user_data->role);
            return;
        }

        $this->revertToGuestRole($user);
    }

    private function revertToGuestRole(User $user): void
    {
        $guest_role = config('defaults.guest_role_id') ?: -1;
        $guest_role = Role::find($guest_role);

        if ($guest_role) {
            $user->roles()->sync([$guest_role]);
        }
    }
}
