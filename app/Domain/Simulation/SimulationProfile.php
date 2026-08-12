<?php

namespace App\Domain\Simulation;

/**
 * Which of the two simulation screens the configuration belongs to. Referral
 * and Account Officer share one calculation chain; the profile selects the few
 * rules that genuinely differ between them.
 */
enum SimulationProfile: string
{
    case REFERRAL = 'referral';
    case OFFICER = 'officer';
}
