<?php

namespace App\Policies;

use App\Models\ActivityLogPhoto;
use App\Models\User;

class ActivityLogPhotoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ActivityLogPhoto $activityLogPhoto): bool
    {
        return $this->ownsPhoto($user, $activityLogPhoto);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ActivityLogPhoto $activityLogPhoto): bool
    {
        return $this->ownsPhoto($user, $activityLogPhoto);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ActivityLogPhoto $activityLogPhoto): bool
    {
        return $this->ownsPhoto($user, $activityLogPhoto);
    }

    private function ownsPhoto(User $user, ActivityLogPhoto $activityLogPhoto): bool
    {
        return $user->id === $activityLogPhoto->activityLog->user_id;
    }
}
