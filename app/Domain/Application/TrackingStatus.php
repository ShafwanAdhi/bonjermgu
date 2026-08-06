<?php

namespace App\Domain\Application;

/**
 * All eleven stages always apply, so unlike documents there is no need for an
 * inapplicable marker (docs/application-tracking.md section 5).
 */
enum TrackingStatus: string
{
    case Belum = 'Belum';
    case Selesai = 'Selesai';

    public function isDone(): bool
    {
        return $this === self::Selesai;
    }
}
