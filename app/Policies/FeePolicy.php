<?php

namespace App\Policies;

use App\Models\Fee;
use App\Models\User;

class FeePolicy
{
    public function view(User $user, Fee $fee): bool
    {
        return $fee->student->user_id === $user->id;
    }

    public function update(User $user, Fee $fee): bool
    {
        return $fee->student->user_id === $user->id;
    }

    public function delete(User $user, Fee $fee): bool
    {
        return $fee->student->user_id === $user->id && $fee->payments()->doesntExist();
    }

    /**
     * Payments may only be recorded against a fee the tutor owns.
     */
    public function pay(User $user, Fee $fee): bool
    {
        return $fee->student->user_id === $user->id;
    }
}
