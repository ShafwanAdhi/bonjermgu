<?php

namespace App\Domain\Application;

enum ApplicationStatus: string
{
    case Pipeline = 'Pipe Line';
    case Canceled = 'Canceled';

    public function isCanceled(): bool
    {
        return $this === self::Canceled;
    }
}
