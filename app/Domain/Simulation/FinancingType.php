<?php

namespace App\Domain\Simulation;

enum FinancingType: string
{
    case DTN = 'DTN';
    case UCF = 'UCF';
}
