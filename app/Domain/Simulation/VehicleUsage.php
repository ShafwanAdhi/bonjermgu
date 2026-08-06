<?php

namespace App\Domain\Simulation;

enum VehicleUsage: string
{
    case PASSENGER = 'Passenger';
    case COMMERCIAL = 'Commercial';
}
