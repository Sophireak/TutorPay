<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a payment would push the total collected for a fee
 * period above the amount billed for that period.
 */
class OverpaymentException extends RuntimeException
{
}
