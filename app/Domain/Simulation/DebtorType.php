<?php

namespace App\Domain\Simulation;

enum DebtorType: string
{
    case ENTREPRENEUR = 'entrepreneur';
    case NON_ENTREPRENEUR = 'non_entrepreneur';
    case LEGAL_ENTITY = 'legal_entity';
}
