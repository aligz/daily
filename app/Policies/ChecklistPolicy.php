<?php

namespace App\Policies;

use App\Models\User;

final class ChecklistPolicy
{
    /**
     * Anyone can see their own checklist. Heads can also read everyone else's.
     */
    public function view(User $viewer, User $owner): bool
    {
        return $viewer->id === $owner->id || $viewer->isHead();
    }

    /**
     * Only the owner can create, check, edit, reorder or archive routines.
     */
    public function manage(User $viewer, User $owner): bool
    {
        return $viewer->id === $owner->id;
    }
}
