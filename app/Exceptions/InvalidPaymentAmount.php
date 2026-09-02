<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a payment with a non-positive (or otherwise invalid)
 * amount is attempted.
 */
class InvalidPaymentAmount extends RuntimeException
{
}
