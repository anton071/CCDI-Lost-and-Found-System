<?php

namespace App\Policies;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function view(User $user, Claim $claim): bool
    {
        return $user->is_active && ($user->isAdmin() || $claim->claimant_user_id === $user->id);
    }

    public function cancel(User $user, Claim $claim): bool
    {
        return $user->is_active
            && $claim->claimant_user_id === $user->id
            && in_array($claim->status, [ClaimStatus::Pending, ClaimStatus::UnderReview], true);
    }

    /** Review/approve/reject/release: administrators only, and never on their own claim. */
    public function review(User $user, Claim $claim): bool
    {
        return $user->isAdmin() && $claim->claimant_user_id !== $user->id;
    }
}
