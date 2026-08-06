<?php

namespace App\Domain\Simulation;

enum StnkOwnership: string
{
    case OWN = 'own';
    case OTHER = 'other';
}
