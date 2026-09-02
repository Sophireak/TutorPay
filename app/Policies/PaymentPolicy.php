<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $payment->student->user_id === $user->id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $payment->student->user_id === $user->id;
    }
}
