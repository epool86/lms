<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $currentUser): bool
    {
        return in_array('admin', $currentUser->roles ?? []);
    }

    public function view(User $currentUser, User $targetUser): bool
    {
        return $this->viewAny($currentUser);
    }

    public function update(User $currentUser, User $targetUser): bool
    {
        return $this->viewAny($currentUser);
    }

    public function delete(User $currentUser, User $targetUser): bool
    {
        if (! $this->viewAny($currentUser)) {
            return false;
        }

        return ! in_array('admin', $targetUser->roles ?? []);
    }

    public function suspend(User $currentUser, User $targetUser): bool
    {
        if (! $this->viewAny($currentUser)) {
            return false;
        }

        return ! in_array('admin', $targetUser->roles ?? []);
    }
}
