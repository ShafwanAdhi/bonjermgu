<?php

namespace App\Domain\Simulation\Output;

/**
 * Why a tenor normalised to zero — credit-simulation.md section 15.
 *
 * A bare value, like the other Domain\Simulation enums. Indonesian copy for
 * each case lives outside the domain layer, alongside the rest of the
 * simulation screens' label methods.
 */
enum ZeroReason: string
{
    case NotEligible = 'not_eligible';
    case RateUnavailable = 'rate_unavailable';
    case PriceUnavailable = 'price_unavailable';
    case DownPaymentExceedsPrice = 'dp_exceeds_price';
    case DownPaymentBelowMinimum = 'dp_below_minimum';
}
